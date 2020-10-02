<?php
/**
 * @author Convert Team
 * @copyright Copyright (c) 2018 Convert (http://www.convert.no/)
 */
namespace Porterbuddy\Porterbuddy\Observer;

use Magento\Quote\Model\Quote;
use Magento\Sales\Model\Order;

class QuoteSubmitPrepareOrder implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * Set quote to order attributes
     *
     * @var array $attributes
     */
    protected $fields = [
        'pb_leave_doorstep',
        'pb_comment',
        'pb_timeslot_selection',
    ];

    /**
     * @var \Magento\Shipping\Model\CarrierFactory
     */
    protected $carrierFactory;

    /**
     * @var \Porterbuddy\Porterbuddy\Helper\Data
     */
    protected $helper;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $localeDate;
    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $session;
    /**
     * @param \Magento\Shipping\Model\CarrierFactory $carrierFactory
     * @param \Porterbuddy\Porterbuddy\Helper\Data $helper
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate
     */
    public function __construct(
        \Magento\Shipping\Model\CarrierFactory $carrierFactory,
        \Porterbuddy\Porterbuddy\Helper\Data $helper,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\Checkout\Model\Session $session
    ) {
        $this->carrierFactory = $carrierFactory;
        $this->helper = $helper;
        $this->localeDate = $localeDate;
        $this->session = $session;
    }

    /**
     * {@inheritdoc}
     *
     * After creating order update shipping description - include full date
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var Quote $quote */
        $quote = $observer->getQuote();
        /** @var Order $order */
        $order = $observer->getOrder();

        if (!$order->getShippingMethod()) {
            return;
        }

        $carrier = $this->carrierFactory->create($order->getShippingMethod(true)->getCarrierCode());
        if (!$carrier instanceof \Porterbuddy\Porterbuddy\Model\Carrier) {
            return;
        }

        $this->changeShippingDescription($order);
        $this->copyFields($quote, $order);

        $token = '';
        $options = $this->session->getPbOptions();
        $methodInfo = $this->helper->parseMethod($order->getShippingMethod());
        foreach( $options as $option) {
            if($option['product'] == $methodInfo->getType() &&
                $option['start'] == $methodInfo->getStart() &&
                $option['end'] == $methodInfo->getEnd()){
                $token = $option['token'];
                break;
            }
        }
        $order->setPbToken($token);

    }

    /**
     * @param Order $order
     * @return void
     */
    protected function changeShippingDescription(Order $order)
    {
        $methodInfo = $this->helper->parseMethod($order->getShippingMethod());
        if (!$methodInfo->getStart()) {
            return;
        }

        // we only need date, but still respect timezone for border cases
        $timezone = $this->helper->getTimezone();
        $start = new \DateTime($methodInfo->getStart());
        $start->setTimezone($timezone);
        $date = $this->localeDate->formatDate($start, \IntlDateFormatter::SHORT);

        $order->setShippingDescription($order->getShippingDescription() . " ($date)");
    }

    /**
     * @param Quote $quote
     * @param Order $order
     * @return void
     */
    protected function copyFields(Quote $quote, Order $order)
    {
        foreach ($this->fields as $attribute) {
            if ($quote->hasData($attribute)) {
                $order->setData($attribute, $quote->getData($attribute));
            }
        }
    }
}
