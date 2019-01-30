<?php
/**
 * @author Convert Team
 * @copyright Copyright (c) 2018 Convert (http://www.convert.no/)
 */
namespace Porterbuddy\Porterbuddy\Model;

use Magento\Directory\Model\CurrencyFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Locale\Format;
use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory;
use Magento\Quote\Model\Quote\Address\RateResult\Method;
use Magento\Quote\Model\Quote\Address\RateResult\MethodFactory;
use Magento\Sales\Model\Order\Shipment;
use Magento\Shipping\Model\Carrier\AbstractCarrier;
use Magento\Shipping\Model\Carrier\CarrierInterface;
use Magento\Shipping\Model\Rate\Result;
use Magento\Shipping\Model\Rate\ResultFactory;
use Magento\Store\Model\ScopeInterface;
use Magento\Email\Model\Template\SenderResolver;
use Porterbuddy\Porterbuddy\Api\Data\MethodInfoInterface;
use Porterbuddy\Porterbuddy\Exception as PorterbuddyException;
use Porterbuddy\Porterbuddy\Exception;
use Porterbuddy\Porterbuddy\Helper\Data;
use Porterbuddy\Porterbuddy\Model\ErrorNotifier\NotifierInterface;
use Psr\Log\LoggerInterface;

class Carrier extends AbstractCarrier implements CarrierInterface
{
    const CODE = 'porterbuddy';

    const METHOD_EXPRESS = 'express';
    const METHOD_EXPRESS_RETURN = 'express-with-return';
    const METHOD_DELIVERY = 'delivery';
    const METHOD_DELIVERY_RETURN = 'delivery-with-return';

    const SHORTCUT_EXPRESS = 'x';
    const SHORTCUT_EXPRESS_RETURN = 'xr';
    const SHORTCUT_DELIVERY = 'd';
    const SHORTCUT_DELIVERY_RETURN = 'dr';

    const MODE_PRODUCTION = 'production';
    const MODE_TESTING = 'testing';
    const MODE_DEVELOPMENT = 'development';

    const WEIGHT_GRAM = 'GRAM';
    const WEIGHT_KILOGRAM = 'KILOGRAM';

    const UNIT_MILLIMETER = 'MILLIMETER';
    const UNIT_CENTIMETER = 'CENTIMETER';

    const DISCOUNT_TYPE_NONE = 'none';
    const DISCOUNT_TYPE_FIXED = 'fixed';
    const DISCOUNT_TYPE_PERCENT = 'percent';

    const TIMESLOT_CHECKOUT = 'checkout';
    const TIMESLOT_CONFIRMATION = 'confirmation';

    const AVAILABILITY_HIDE = 'hide';
    const AVAILABILITY_ONLY_AVAILABLE = 'only_available';
    const AVAILABILITY_ALWAYS = 'always';

    const LOCATION_BROWSER = 'browser';
    const LOCATION_IP = 'ip';

    const COOKIE = 'porterbuddy_location';

    const SOURCE_BROWSER = 'browser';
    const SOURCE_IP = 'ip';
    const SOURCE_USER = 'user';

    protected $_code = self::CODE;

    /**
     * @var Api
     */
    protected $api;

    /**
     * @var NotifierInterface
     */
    protected $errorNotifier;

    /**
     * @var CurrencyFactory
     */
    protected $currencyFactory;

    /**
     * @var ManagerInterface
     */
    protected $eventManager;

    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var Format
     */
    protected $localeFormat;

    /**
     * @var \Magento\Sales\Model\Order\Shipment\TrackFactory
     */
    protected $salesOrderShipmentTrackFactory;

    /**
     * @var Packager
     */
    protected $packager;

    /**
     * @var Timeslots
     */
    protected $timeslots;

    /**
     * @var MethodFactory
     */
    protected $rateMethodFactory;

    /**
     * @var float
     */
    protected $baseCurrencyRate;

    /**
     * @var ResultFactory
     */
    protected $rateResultFactory;

    /**
     * @var \Magento\Email\Model\Template\SenderResolver
     */
    protected $senderResolver;

    public function __construct(
        Api $api,
        NotifierInterface $errorNotifier,
        CurrencyFactory $currencyFactory,
        ManagerInterface $eventManager,
        Data $helper,
        Format $localeFormat,
        MethodFactory $rateMethodFactory,
        ResultFactory $rateResultFactory,
        \Magento\Sales\Model\Order\Shipment\TrackFactory $trackFactory,
        Packager $packager,
        Timeslots $timeslots,
        ScopeConfigInterface $scopeConfig,
        SenderResolver $senderResolver,
        ErrorFactory $rateErrorFactory,
        LoggerInterface $logger,
        array $data = []
    ) {
        $this->api = $api;
        $this->errorNotifier = $errorNotifier;
        $this->currencyFactory = $currencyFactory;
        $this->eventManager = $eventManager;
        $this->helper = $helper;
        $this->localeFormat = $localeFormat;
        $this->rateMethodFactory = $rateMethodFactory;
        $this->rateResultFactory = $rateResultFactory;
        $this->salesOrderShipmentTrackFactory = $trackFactory;
        $this->packager = $packager;
        $this->timeslots = $timeslots;
        $this->senderResolver = $senderResolver;
        parent::__construct($scopeConfig, $rateErrorFactory, $logger, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function proccessAdditionalValidation(\Magento\Framework\DataObject $request)
    {
        if ('NO' !== $request->getDestCountryId()) {
            $this->_logger->warning("Destination country `{$request->getDestCountryId()}` is not supported.");
            return false;
        }
        if (!strlen($request->getDestPostcode())) {
            $this->_logger->warning("Empty postcode ignored.");
            return false;
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getDeliveryConfirmationTypes(\Magento\Framework\DataObject $params = null)
    {
        // make default option preselected as it goes first
        if ($this->helper->isRequireSignatureDefault()) {
            return [
                1 => __('Signature Required'),
                0 => __('Not Required'),
            ];
        } else {
            return [
                0 => __('Not Required'),
                1 => __('Signature Required'),
            ];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getContentTypes(\Magento\Framework\DataObject $params)
    {
        return [
            '' => __('-- Product names --'),
            'OTHER' => __('Other'),
        ];
    }

    /**
     * Interface method used to get additional tracking info in tracking popup
     *
     * @param string $number
     * @return \Magento\Framework\DataObject|array
     */
    public function getTrackingInfo($number)
    {
        /** @var \Magento\Sales\Model\Order\Shipment\Track $track */
        $track = $this->salesOrderShipmentTrackFactory->create()->load($number, 'track_number');

        return [
            'title' => $track->getTitle(),
            'number' => $number,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function collectRates(RateRequest $request)
    {
        if (!$this->helper->getActive()) {
            return false;
        }

        /** @var Result $result */
        $result = $this->rateResultFactory->create();

        // multi warehousing may disable this method if items are physically located in different places
        $this->eventManager->dispatch('porterbuddy_collect_rates', [
            'request' => $request,
            'result' => $result,
        ]);
        if ($result->getError()) {
            return $result;
        }

        $items = $request->getAllItems();
        if (!$items) {
            return $result;
        }

        /** @var \Magento\Quote\Model\Quote\Item $item */
        $item = reset($items);
        $quote = $item->getQuote();
        $quote->setPbTimeslotSelection($this->helper->getTimeslotSelection());

        try {
            $parameters = $this->prepareAvailabilityData($request);
            $options = $this->api->getAvailability($parameters);
        } catch (\Porterbuddy\Porterbuddy\Exception $e) {
            // details logged
            return $result;
        } catch (\Exception $e) {
            // other unexpected errors
            $this->_logger->error($e);
            return $result;
        }

        $expressOptions = []; // no return + with return
        $scheduledOptions = [];

        foreach ($options as $option) {
            if (in_array($option['product'], [self::METHOD_EXPRESS, self::METHOD_EXPRESS_RETURN])) {
                $expressOptions[] = $option;
            } elseif (in_array($option['product'], [self::METHOD_DELIVERY, self::METHOD_DELIVERY_RETURN])) {
                $scheduledOptions[] = $option;
            }
        }

        if (true === $request->getFreeShipping()) {
            $this->_logger->info("collectRates - free shipping for quote {$quote->getId()}", [
                'applied_rule_ids' => $quote->getAppliedRuleIds(),
                'discount_description' => $quote->getShippingAddress()->getDiscountDescription(),
            ]);
        }

        if ($expressOptions) {
            foreach ($expressOptions as $option) {
                try {
                    $result = $this->addRateResult($request, $option, $result);
                } catch (Exception $e) {
                    $this->_logger->warning("Availability option error - {$e->getMessage()}.", $option);
                    $this->_logger->warning($e, $option);
                }
            }
        }

        if ($this->helper->getShowTimeslots()) {
            foreach ($scheduledOptions as $option) {
                try {
                    $result = $this->addRateResult($request, $option, $result);
                } catch (Exception $e) {
                    $this->_logger->warning("Availability option error - {$e->getMessage()}.", $option);
                    $this->_logger->warning($e, $option);
                }
            }
        } else {
            // first scheduled option to get price
            $option = reset($scheduledOptions);
            if ($option) {
                try {
                    $result = $this->addDeliveryOnConfirmationResult($request, $option, $result);
                } catch (Exception $e) {
                    $this->_logger->warning("Availability option error - {$e->getMessage()}.", $option);
                    $this->_logger->warning($e, $option);
                }
            }
        }

        $result = $this->applyDiscounts($request, $result);

        // enable to construct new result object
        $transport = new DataObject(['result' => $result]);
        $this->eventManager->dispatch('porterbuddy_collect_rates_after', [
            'request' => $request,
            'transport' => $transport,
        ]);
        $result = $transport->getData('result');

        return $result;
    }

    /**
     * @param RateRequest $request
     * @param array $option
     * @param Result $result
     * @return Result
     * @throws Exception
     */
    public function addDeliveryOnConfirmationResult(
        RateRequest $request,
        array $option,
        Result $result
    ) {
        // Local timezone
        /** @var Method $method */
        $method = $this->rateMethodFactory->create();

        $method->setCarrier(self::CODE);
        $method->setCarrierTitle($this->helper->getTitle());

        $methodCode = $this->helper->makeMethodCode($option, true);
        $method->setMethod($methodCode); // no start-end dates

        $method->setMethodTitle(__('Select specific time after checkout')); // $this->helper->getScheduledName()
        //$method->setMethodDescription($this->helper->getScheduledDescription());

        if ($request->getFreeShipping() === true) {
            $shippingPrice = '0.00';
        } else {
            $shippingPrice = $this->helper->getPriceOverrideDelivery();

            if (null === $shippingPrice
                && isset($option['price']['fractionalDenomination'], $option['price']['currency'])
            ) {
                $apiPrice = $this->localeFormat->getNumber($option['price']['fractionalDenomination']) / 100;
                $rate = $this->getBaseCurrencyRate($request, $option['price']['currency']);
                $shippingPrice = $apiPrice * $rate;
            }
            if (null === $shippingPrice) {
                $this->_logger->warning('Skip option with undefined price', ['option' => $option]);
                return $result;
            }
        }

        $method->setPrice($shippingPrice);
        $method->setCost($shippingPrice);

        $result->append($method);

        return $result;
    }

    /**
     * @param RateRequest $request
     * @param array $option
     * @param Result $result
     * @return Result
     * @throws Exception
     */
    public function addRateResult(
        RateRequest $request,
        array $option,
        Result $result
    ) {
        $methodCode = $this->helper->makeMethodCode($option);

        // convert to standard interface
        $methodInfo = $this->helper->parseMethod($methodCode);

        // Local timezone
        $methodTitle = $this->timeslots->formatTimeslot($methodInfo);

        /** @var Method $method */
        $method = $this->rateMethodFactory->create();

        $method->setCarrier(self::CODE);
        $method->setCarrierTitle($this->helper->getTitle());

        $method->setMethod($methodCode);
        $method->setMethodTitle($methodTitle); // $this->helper->getScheduledName()
        //$method->setMethodDescription($this->helper->getScheduledDescription());

        if ($request->getFreeShipping() === true) {
            $shippingPrice = '0.00';
        } else {
            switch ($methodInfo->getProduct()) {
                case self::METHOD_EXPRESS:
                    $shippingPrice = $this->helper->getPriceOverrideExpress();
                    break;
                case self::METHOD_EXPRESS_RETURN:
                    $shippingPrice = $this->helper->getPriceOverrideExpressReturn();
                    break;
                case self::METHOD_DELIVERY:
                    $shippingPrice = $this->helper->getPriceOverrideDelivery();
                    break;
                case self::METHOD_DELIVERY_RETURN:
                    $shippingPrice = $this->helper->getPriceOverrideDeliveryReturn();
                    break;
                default:
                    $shippingPrice = null;
            }

            if (null === $shippingPrice
                && isset($option['price']['fractionalDenomination'], $option['price']['currency'])
            ) {
                $apiPrice = $this->localeFormat->getNumber($option['price']['fractionalDenomination']) / 100;
                $rate = $this->getBaseCurrencyRate($request, $option['price']['currency']);
                $shippingPrice = $apiPrice * $rate;
            }
            if (null === $shippingPrice) {
                $this->_logger->warning('Skip option with undefined price', ['option' => $option]);
                return $result;
            }
        }

        $method->setPrice($shippingPrice);
        $method->setCost($shippingPrice);

        $result->append($method);

        return $result;
    }

    /**
     * @param RateRequest $request
     * @return float
     */
    public function getBaseCurrencyRate(RateRequest $request, $responseCurrencyCode = 'NOK')
    {
        if (null === $this->baseCurrencyRate) {
            // TODO: throw error if base currency rate is not defined
            /** @var \Magento\Directory\Model\Currency $currency */
            $currency = $this->currencyFactory->create();
            $this->baseCurrencyRate = $currency
                ->load($responseCurrencyCode)
                ->getAnyRate($request->getBaseCurrency()->getCode());
        }
        return $this->baseCurrencyRate;
    }

    /**
     * Applies discounts based on cart subtotal threshold
     *
     * @param RateRequest $request
     * @param Result $result
     * @return Result
     */
    public function applyDiscounts(
        RateRequest $request,
        Result $result
    ) {
        $discountType = $this->helper->getDiscountType();
        $discountSubtotal = $this->helper->getDiscountSubtotal();

        // known possible problem: $request->getBaseSubtotalInclTax can be empty in some cases, same problem with
        // free shipping. This is because shipping total collector is called before tax subtotal collector, and so
        // BaseSubtotalInclTax is not updated yet.
        if ($request->getBaseSubtotalInclTax() < $discountSubtotal) {
            // we need more gold
            return $result;
        }

        if (self::DISCOUNT_TYPE_FIXED === $discountType) {
            $discountAmount = $this->helper->getDiscountAmount();
            if ($discountAmount <= 0) {
                $this->_logger->warning("Invalid discount amount `$discountAmount`.");
                return $result;
            }

            foreach ($result->getAllRates() as $method) {
                $price = $method->getPrice();
                if ($price > 0) {
                    $price -= $discountAmount;
                    $price = max($price, 0.00);
                    $method->setPrice(max($price, 0.00));
                }
            }
        } elseif (self::DISCOUNT_TYPE_PERCENT === $discountType) {
            $discountPercent = $this->helper->getDiscountPercent();
            if ($discountPercent <= 0 || $discountPercent > 100) {
                $this->_logger->warning("Invalid discount percent `$discountPercent`.");
                return $result;
            }

            foreach ($result->getAllRates() as $method) {
                $price = $method->getPrice();
                if ($price > 0) {
                    $price -= $price * $discountPercent / 100;
                    $price = max($price, 0.00);
                    $method->setPrice($price);
                }
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function isTrackingAvailable()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isShippingLabelsAvailable()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Porterbuddy\Porterbuddy\Exception
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function requestToShipment($request)
    {
        $result = new DataObject();

        $shipment = $request->getOrderShipment();

        $this->_logger->notice('requestToShipment - start.', [
            'order_id' => $shipment->getOrderId(),
            'shipment_id' => $shipment->getId(),
        ]);

        // mark it processed to disable auto creating label, we already processed it
        $shipment->setIsPorterbuddySent(true);

        try {
            $parameters = $this->prepareCreateOrderData($request);
            $idempotencyKey = $this->getShipmentIdempotencyKey($request);
            $orderDetails = $this->api->createOrder($parameters, $idempotencyKey);
        } catch (\Porterbuddy\Porterbuddy\Exception $e) {
            $this->errorNotifier->notify($e, $shipment, $request);
            $shipment->setPorterbuddyErrorNotified(true);

            // details logged
            $result->setErrors($e->getMessage());
            //return $result;
            throw $e;
        } catch (\Exception $e) {
            $this->errorNotifier->notify($e, $shipment, $request);
            $shipment->setPorterbuddyErrorNotified(true);

            // other unexpected errors
            $this->_logger->error($e);
            $result->setErrors($e->getMessage());
            //return $result;
            throw $e;
        }

        // TODO: save order iframe URL for timeslot selection on confirmation page

        $comment = __('Porterbuddy shipment has been ordered.');
        if (!empty($orderDetails['deliveryReference'])) {
            $comment .= ' ' . __('Delivery reference %1', $orderDetails['deliveryReference']);
        }
        $shipment->addComment($comment);

        // Magento requires returning pairs shipping label-tracking number. As we don't support actual labels yet,
        // we can't provide tracking numbers by standard mechanism either. So we will assign them manually to shipment

        $trackingNumber = $orderDetails['orderId'];
        /** @var \Magento\Sales\Model\Order\Shipment\Track $track */
        $track = $this->salesOrderShipmentTrackFactory->create();
        $track
            ->setNumber($trackingNumber)
            ->setCarrierCode($this->getCarrierCode())
            ->setTitle(__('Order ID'));
        $shipment->addTrack($track);

        if (!empty($orderDetails['deliveryReference'])) {
            $trackingNumber = $orderDetails['deliveryReference'];
            /** @var \Magento\Sales\Model\Order\Shipment\Track $track */
            $track = $this->salesOrderShipmentTrackFactory->create();
            $track
                ->setNumber($trackingNumber)
                ->setCarrierCode($this->getCarrierCode())
                ->setTitle(__('Delivery Reference'));
            $shipment->addTrack($track);
        }

        $result->setInfo([]); // mark as success

        $this->_logger->notice('requestToShipment - success.', [
            'order_id' => $shipment->getOrderId(),
            'shipment_id' => $shipment->getId(),
        ]);

        return $result;
    }

    /**
     * @param \Magento\Shipping\Model\Shipment\Request $request
     * @return string|null
     */
    public function getShipmentIdempotencyKey(\Magento\Shipping\Model\Shipment\Request $request)
    {
        $shipment = $request->getOrderShipment();
        $order = $shipment->getOrder();

        // TODO: for part shipping, include items
        return $order->getIncrementId();
    }

    /**
     * {@inheritdoc}
     */
    public function getAllowedMethods()
    {
        return [
            self::SHORTCUT_EXPRESS => __('Express'),
            self::SHORTCUT_DELIVERY => __('Delivery'),
        ];
    }

    /**
     * @param RateRequest $request
     * @return array
     * @throws \Porterbuddy\Porterbuddy\Exception
     */
    public function prepareAvailabilityData(RateRequest $request)
    {
        $params = [];

        $params['pickupWindows'] = $this->timeslots->getAvailabilityPickupWindows($request);

        $originStreet1 = $this->_scopeConfig->getValue(Shipment::XML_PATH_STORE_ADDRESS1, ScopeInterface::SCOPE_STORE);
        $originStreet2 = $this->_scopeConfig->getValue(Shipment::XML_PATH_STORE_ADDRESS2, ScopeInterface::SCOPE_STORE);
        $params['originAddress'] = [
            'streetName' => trim("$originStreet1 $originStreet2"),
            'streetNumber' => ',', // FIXME: set empty when API is updated
            'postalCode' => $this->_scopeConfig->getValue(Shipment::XML_PATH_STORE_ZIP, ScopeInterface::SCOPE_STORE),
            'city' => $this->_scopeConfig->getValue(Shipment::XML_PATH_STORE_CITY, ScopeInterface::SCOPE_STORE),
            'country' => $this->_scopeConfig->getValue(Shipment::XML_PATH_STORE_COUNTRY_ID, ScopeInterface::SCOPE_STORE),
        ];

        $streetName = $request->getDestStreet();
        if (is_array($streetName)) {
            $streetName = implode(' ', $streetName);
        }

        $params['destinationAddress'] = [
            'streetName' => $streetName,
            //'streetNumber' => '',
            'postalCode' => $request->getDestPostcode(),
            'city' => $request->getDestCity(),
            'country' => $request->getDestCountryId(),
        ];

        // create availability check context
        $params['parcels'] = $this->packager->estimateParcels($request);
        $params['products'] = [self::METHOD_EXPRESS, self::METHOD_DELIVERY];

        if ($this->helper->getReturnEnabled()) {
            $params['products'][] = self::METHOD_EXPRESS_RETURN;
            $params['products'][] = self::METHOD_DELIVERY_RETURN;
        }

        $transport = new DataObject(['params' => $params]);
        $this->eventManager->dispatch('porterbuddy_availability_data', [
            'request' => $request,
            'transport' => $transport,
        ]);
        $params = $transport->getData('params');

        return $params;
    }

    /**
     * Prepares request payload for create order API call
     *
     * @param \Magento\Shipping\Model\Shipment\Request $request
     * @return array
     * @throws \Porterbuddy\Porterbuddy\Exception
     */
    protected function prepareCreateOrderData(\Magento\Shipping\Model\Shipment\Request $request)
    {
        $shipment = $request->getOrderShipment();
        $order = $shipment->getOrder();
        $methodInfo = $this->helper->parseMethod($request->getShippingMethod());

        $methodInfo = $this->checkExpiredTimeslot($methodInfo);
        $parcels = $this->getParcels($request, $shipment);

        if (!$request->getRecipientEmail()) {
            $request->setRecipientEmail($order->getCustomerEmail());
        }

        $defaultPhoneCode = $this->helper->getDefaultPhoneCode();
        $pickupPhone = $this->helper->splitPhoneCodeNumber($request->getShipperContactPhoneNumber());
        $deliveryPhone = $this->helper->splitPhoneCodeNumber($request->getRecipientContactPhoneNumber());

        $deliveryTimeslotIsKnown = ($methodInfo->getStart() && $methodInfo->getEnd());

        $senderlIdentity = $this->helper->getOrderEmailIdentity($shipment->getStoreId());
        $senderResult = $this->senderResolver->resolve($senderlIdentity,$shipment->getStoreId());

        $parameters = [
            'origin' => [
                'name' => $senderResult['name'],
                'address' => [
                    'streetName' => $request->getShipperAddressStreet(),
                    'streetNumber' => ',', // TODO: remove when API ready
                    'postalCode' => $request->getShipperAddressPostalCode(),
                    'city' => $request->getShipperAddressCity(),
                    'country' => $request->getShipperAddressCountryCode(),
                ],
                'email' => $senderResult['email'],
                'phoneCountryCode' => $pickupPhone[0] ?: $defaultPhoneCode,
                'phoneNumber' => $pickupPhone[1],
                'pickupWindows' => $this->timeslots->getPickupWindows($methodInfo),
            ],
            'destination' => [
                'name' => $request->getRecipientContactPersonName(),
                'address' => [
                    'streetName' => $request->getRecipientAddressStreet(),
                    'streetNumber' => ',', // TODO: remove when API ready
                    'postalCode' => $request->getRecipientAddressPostalCode(),
                    'city' => $request->getRecipientAddressCity(),
                    'country' => $request->getRecipientAddressCountryCode(),
                ],
                'email' => $request->getRecipientEmail(),
                'phoneCountryCode' => $deliveryPhone[0] ?: $defaultPhoneCode,
                'phoneNumber' => $deliveryPhone[1],
                'deliveryWindow' => $deliveryTimeslotIsKnown ? [
                    'start' => $this->helper->formatApiDateTime($methodInfo->getStart()),
                    'end' => $this->helper->formatApiDateTime($methodInfo->getEnd()),
                ] : null,
                'bestAvailableWindow' => !$deliveryTimeslotIsKnown,
                'verifications' => $this->getVerifications($shipment),
            ],
            'parcels' => $parcels,
            'product' => $methodInfo->getProduct(),
            'orderReference' => $order->getIncrementId(),
            'courierInstructions' => $order->getPbComment() ?: '',
        ];

        $transport = new DataObject(['parameters' => $parameters]);
        $this->eventManager->dispatch('porterbuddy_create_order_data', [
            'transport' => $transport,
            'request' => $request,
        ]);
        $parameters = $transport->getData('parameters');

        return $parameters;
    }

    /**
     * For passed dates, get new closest timeslot
     *
     * @param MethodInfoInterface $methodInfo
     * @return MethodInfoInterface
     * @throws \Porterbuddy\Porterbuddy\Exception
     */
    public function checkExpiredTimeslot(MethodInfoInterface $methodInfo)
    {
        $scheduledDate = new \DateTime($methodInfo->getStart());
        $currentTime = $this->helper->getCurrentTime();
        if ($currentTime > $scheduledDate) {
            $this->_logger->error("Delivery timeslot `{$methodInfo->getStart()}` expired.");
            // FIXME
            // throw new \Porterbuddy\Porterbuddy\Exception(__('Delivery timeslot %1 expired', $methodInfo->getStart()));
        }
        return $methodInfo;
    }

    /**
     * Creates shipment packages if needed, exports to Porterbuddy API format
     *
     * @param \Magento\Shipping\Model\Shipment\Request $request
     * @param \Magento\Sales\Model\Order\Shipment $shipment
     * @return array
     * @throws \Porterbuddy\Porterbuddy\Exception
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getParcels(
        \Magento\Shipping\Model\Shipment\Request $request,
        \Magento\Sales\Model\Order\Shipment $shipment
    ) {
        if (!$shipment->getPackages() || !is_array($shipment->getPackages())) {
            $packages = $this->packager->createPackages($request);
            $shipment->setPackages($packages);
            $this->_logger->notice('Automatically created packages.');
        } else {
            $this->_logger->notice('Packages already created.');
        }

        $shipment->setPackages($shipment->getPackages());

        $parcels = $this->packager->getParcelsFromPackages($shipment);
        if (!$parcels) {
            $this->_logger->error(
                "Error preparing order data for shipment `{$shipment->getId()}`, empty parcels",
                [
                    'packages' => $shipment->getPackages()
                ]
            );
            throw new \Porterbuddy\Porterbuddy\Exception(__('There was an error preparing parcels.'));
        }
        return $parcels;
    }

    /**
     * Returns verification options based on shipment packages params, products and default settings
     *
     * @param \Magento\Sales\Model\Order\Shipment $shipment
     * @return array
     */
    public function getVerifications(\Magento\Sales\Model\Order\Shipment $shipment)
    {
        $order = $shipment->getOrder();
        $packages = $shipment->getPackages();
        if ($packages && is_scalar($packages)) {
            $packages = unserialize($packages);
        }

        $verifications = [];
        $verifications['leaveAtDoorstep'] = (bool)$order->getPbLeaveDoorstep();

        // when creating packages manually, admin selects signature confirmation.
        // if at least one package requires signature. treat whole order requiring as well
        $requireSignature = null;
        foreach ($packages as $package) {
            $package = new DataObject($package);
            $value = $package->getData('params/delivery_confirmation');
            if (null !== $value) {
                $requireSignature = $requireSignature || $value;
            }
        }

        if (null !== $requireSignature) {
            $verifications['requireSignature'] = $requireSignature;
        } else {
            // signature requirement not set explicitly in packages, check products
            $verifications['requireSignature'] = $this->isVerificationRequired(
                $shipment,
                $this->helper->isRequireSignatureDefault(),
                $this->helper->getRequireSignatureAttr()
            );
        }

        $minAge = $this->helper->getMinAgeCheckDefault();
        $minAgeAttr = $this->helper->getMinAgeCheckAttr();
        if ($minAgeAttr) {
            /** @var \Magento\Sales\Model\Order\Shipment\Item $item */
            foreach ($shipment->getAllItems() as $item) {
                $product = $item->getOrderItem()->getProduct();
                $value = $product->getData($minAgeAttr);
                if ($product->hasData($minAgeAttr) && is_numeric($value) && $value > 0) {
                    $minAge = $minAge ? min($minAge, $value) : $value;
                }
            }
        }
        if ($minAge) {
            $verifications['minimumAgeCheck'] = $minAge;
        }

        $verifications['idCheck'] = $this->isVerificationRequired(
            $shipment,
            $this->helper->isIdCheckDefault(),
            $this->helper->getIdCheckAttr()
        );

        $verifications['onlyToRecipient'] = $this->isVerificationRequired(
            $shipment,
            $this->helper->isOnlyToRecipientDefault(),
            $this->helper->getOnlyToRecipientAttr()
        );

        return $verifications;
    }

    /**
     * Checks if verification is required
     *
     * - always required if set by default
     * - required if product attribute is set and at least one product in order is marked true
     *
     * @param \Magento\Sales\Model\Order\Shipment $shipment
     * @param int $default
     * @param string|null $attributeCode
     * @return bool
     */
    protected function isVerificationRequired(\Magento\Sales\Model\Order\Shipment $shipment, $default, $attributeCode)
    {
        $result = $default;

        if (!$result && $attributeCode) {
            // true if at least one product is true
            /** @var \Magento\Sales\Model\Order\Shipment\Item $item */
            foreach ($shipment->getAllItems() as $item) {
                $product = $item->getOrderItem()->getProduct();
                if ($product->getData($attributeCode)) {
                    $result = true;
                    break;
                }
            }
        }

        return $result;
    }
}
