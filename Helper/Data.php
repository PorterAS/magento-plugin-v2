<?php
/**
 * @author Convert Team
 * @copyright Copyright (c) 2018 Convert (http://www.convert.no/)
 */
namespace Porterbuddy\Porterbuddy\Helper;

use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\ScopeInterface;
use Porterbuddy\Porterbuddy\Api\Data\MethodInfoInterface;
use Porterbuddy\Porterbuddy\Exception;
use Porterbuddy\Porterbuddy\Model\Carrier;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const API_DATE_FORMAT = \DateTime::ATOM;

    const XML_PATH_ACTIVE = 'carriers/porterbuddy/active';
    const XML_PATH_TITLE = 'carriers/porterbuddy/title';
    const XML_PATH_DESCRIPTION = 'carriers/porterbuddy/description';
    const XML_PATH_ASAP_NAME = 'carriers/porterbuddy/asap_name';
    const XML_PATH_AUTO_CREATE_SHIPMENT = 'carriers/porterbuddy/auto_create_shipment';
    const XML_PATH_API_MODE = 'carriers/porterbuddy/api_mode';
    const XML_PATH_API_TIMEOUT = 'carriers/porterbuddy/api_timeout';
    const XML_PATH_TESTING_API_URL = 'carriers/porterbuddy/testing_api_url';
    const XML_PATH_TESTING_API_KEY = 'carriers/porterbuddy/testing_api_key';
    const XML_PATH_TESTING_PUBLIC_KEY = 'carriers/porterbuddy/testing_public_key';
    const XML_PATH_PRODUCTION_API_URL = 'carriers/porterbuddy/production_api_url';
    const XML_PATH_PRODUCTION_API_KEY = 'carriers/porterbuddy/production_api_key';
    const XML_PATH_PRODUCTION_PUBLIC_KEY = 'carriers/porterbuddy/production_public_key';
    const XML_PATH_WIDGET_URL = 'carriers/porterbuddy/widget_url';

    const XML_PATH_INBOUND_TOKEN = 'carriers/porterbuddy/inbound_token';

    const XML_PATH_SHOW_AVAILABILITY = 'carriers/porterbuddy/show_availability';
    const XML_PATH_AVAILABILITY_ALTERNATE_VIEW = 'carriers/porterbuddy/availability_alternate_view';
    const XML_PATH_LOCATION_LINK_TEMPLATE = 'carriers/porterbuddy/location_link_template';
    const XML_PATH_AVAILABILITY_TEMPLATE = 'carriers/porterbuddy/availability_template';
    const XML_PATH_AVAILABILITY_TEXT_FETCHING = 'carriers/porterbuddy/availability_text_fetching';
    const XML_PATH_AVAILABILITY_TEXT_CLICK_TO_SEE = 'carriers/porterbuddy/availability_text_click_to_see';
    const XML_PATH_AVAILABILITY_TEXT_POSTCODE_ERROR = 'carriers/porterbuddy/availability_text_postcode_error';
    const XML_PATH_AVAILABILITY_TEXT_OUT_OF_STOCK = 'carriers/porterbuddy/availability_text_delivery_out_of_stock';
    const XML_PATH_AVAILABILITY_TEXT_NO_DATE = 'carriers/porterbuddy/availability_text_delivery_no_date';
    const XML_PATH_AVAILABILITY_AUTO_UPDATE_COMPOSITE = 'carriers/porterbuddy/availability_auto_update_composite';

    const XML_PATH_ADDRESS_WARNING_TEXT = 'carriers/porterbuddy/address_warning_text';
    const XML_PATH_ADDRESS_WARNING_CLOSE_LABEL = 'carriers/porterbuddy/address_warning_close_label';
    const XML_PATH_ADDRESS_WARNING_TITLE = 'carriers/porterbuddy/address_warning_title';

    const XML_PATH_DEFAULT_PHONE_CODE = 'carriers/porterbuddy/default_phone_code';
    const XML_PATH_PACKAGER_MODE = 'carriers/porterbuddy/packager_mode';
    const XML_PATH_INVENTORY_STOCK = 'carriers/porterbuddy/inventory_stock';
    const XML_PATH_PACKING_TIME = 'carriers/porterbuddy/packing_time';
    const XML_PATH_USE_DONOR = 'carriers/porterbuddy/use_donor';
    const XML_PATH_PRESELECT_LOCATION = 'carriers/porterbuddy/preselect_location';
    const XML_PATH_RETURN_ENABLED = 'carriers/porterbuddy/return_enabled';
    const XML_PATH_DAYS_AHEAD = 'carriers/porterbuddy/days_ahead';
    const XML_PATH_EXTRA_PICKUP_WINDOWS = 'carriers/porterbuddy/pickup_windows_extra';

    const XML_PATH_PRICE_OVERRIDE_EXPRESS = 'carriers/porterbuddy/price_override_express';
    const XML_PATH_PRICE_OVERRIDE_EXPRESS_RETURN = 'carriers/porterbuddy/price_override_express_return';
    const XML_PATH_PRICE_OVERRIDE_DELIVERY = 'carriers/porterbuddy/price_override_delivery';
    const XML_PATH_PRICE_OVERRIDE_DELIVERY_RETURN = 'carriers/porterbuddy/price_override_delivery_return';

    const XML_PATH_DISCOUNTS = 'carriers/porterbuddy/discounts';

    const XML_PATH_HOURS_MON = 'carriers/porterbuddy/hours_mon';
    const XML_PATH_HOURS_TUE = 'carriers/porterbuddy/hours_tue';
    const XML_PATH_HOURS_WED = 'carriers/porterbuddy/hours_wed';
    const XML_PATH_HOURS_THU = 'carriers/porterbuddy/hours_thu';
    const XML_PATH_HOURS_FRI = 'carriers/porterbuddy/hours_fri';
    const XML_PATH_HOURS_SAT = 'carriers/porterbuddy/hours_sat';
    const XML_PATH_HOURS_SUN = 'carriers/porterbuddy/hours_sun';

    const XML_PATH_PORTERBUDDY_UNTIL = 'carriers/porterbuddy/porterbuddy_until';
    const XML_PATH_PORTERBUDDY_UNTIL_MON = 'carriers/porterbuddy/porterbuddy_until_mon';
    const XML_PATH_PORTERBUDDY_UNTIL_TUE = 'carriers/porterbuddy/porterbuddy_until_tue';
    const XML_PATH_PORTERBUDDY_UNTIL_WED = 'carriers/porterbuddy/porterbuddy_until_wed';
    const XML_PATH_PORTERBUDDY_UNTIL_THU = 'carriers/porterbuddy/porterbuddy_until_thu';
    const XML_PATH_PORTERBUDDY_UNTIL_FRI = 'carriers/porterbuddy/porterbuddy_until_fri';
    const XML_PATH_PORTERBUDDY_UNTIL_SAT = 'carriers/porterbuddy/porterbuddy_until_sat';
    const XML_PATH_PORTERBUDDY_UNTIL_SUN = 'carriers/porterbuddy/porterbuddy_until_sun';

    const XML_PATH_REQUIRE_SIGNATURE_DEFAULT = 'carriers/porterbuddy/require_signature_default';
    const XML_PATH_MIN_AGE_CHECK_DEFAULT = 'carriers/porterbuddy/min_age_check_default';
    const XML_PATH_ID_CHECK_DEFAULT = 'carriers/porterbuddy/id_check_default';
    const XML_PATH_ONLY_RECIPIENT_DEFAULT = 'carriers/porterbuddy/only_to_recipient_default';

    const XML_PATH_REQUIRE_SIGNATURE_ATTR = 'carriers/porterbuddy/require_signature_attr';
    const XML_PATH_MIN_AGE_CHECK_ATTR = 'carriers/porterbuddy/min_age_check_attr';
    const XML_PATH_ID_CHECK_ATTR = 'carriers/porterbuddy/id_check_attr';
    const XML_PATH_ONLY_RECIPIENT_ATTR = 'carriers/porterbuddy/only_to_recipient_attr';

    const XML_PATH_CHECKOUT_WIDGET_TITLE = 'carriers/porterbuddy/checkout_widget_title';
    const XML_PATH_ENTER_POSTCODE_TEXT = 'carriers/porterbuddy/enter_postcode_text';

    const XML_PATH_HOME_DELIVERY_TITLE = 'carriers/porterbuddy/home_delivery_title';
    const XML_PATH_PICKUP_POINT_TITLE = 'carriers/porterbuddy/pickup_point_title';
    const XML_PATH_COLLECT_IN_STORE_TITLE = 'carriers/porterbuddy/collect_in_store_title';

    const XML_PATH_RETURN_TEXT = 'carriers/porterbuddy/return_text';
    const XML_PATH_RETURN_SHORT_TEXT = 'carriers/porterbuddy/return_short_text';
    const XML_PATH_REFRESH_OPTIONS_TIMEOUT = 'carriers/porterbuddy/refresh_options_timeout';
    const XML_PATH_LEAVE_DOORSTEP_ENABLED = 'carriers/porterbuddy/leave_doorstep_enabled';
    const XML_PATH_LEAVE_DOORSTEP_TEXT = 'carriers/porterbuddy/leave_doorstep_text';
    const XML_PATH_COMMENT_TEXT = 'carriers/porterbuddy/comment_text';
    const XML_PATH_HIDE_SHIPPING_EMPTY_ADDRESS = 'carriers/porterbuddy/hide_shipping_empty_address';
    const XML_PATH_WEIGHT_UNIT = 'carriers/porterbuddy/weight_unit';
    const XML_PATH_DIMENSION_UNIT = 'carriers/porterbuddy/dimension_unit';
    const XML_PATH_DEFAULT_PRODUCT_WEIGHT = 'carriers/porterbuddy/default_product_weight';
    const XML_PATH_HEIGHT_ATTRIBUTE = 'carriers/porterbuddy/height_attribute';
    const XML_PATH_WIDTH_ATTRIBUTE = 'carriers/porterbuddy/width_attribute';
    const XML_PATH_LENGTH_ATTRIBUTE = 'carriers/porterbuddy/length_attribute';
    const XML_PATH_DEFAULT_PRODUCT_HEIGHT = 'carriers/porterbuddy/default_product_height';
    const XML_PATH_DEFAULT_PRODUCT_WIDTH = 'carriers/porterbuddy/default_product_width';
    const XML_PATH_DEFAULT_PRODUCT_LENGTH = 'carriers/porterbuddy/default_product_length';

    const XML_PATH_ORDER_EMAIL_IDENTITY = 'carriers/porterbuddy/order_email_identity';

    const XML_PATH_ERROR_EMAIL_ENABLED = 'carriers/porterbuddy/error_email_enabled';
    const XML_PATH_ERROR_EMAIL_IDENTITY = 'carriers/porterbuddy/error_email_identity';
    const XML_PATH_ERROR_EMAIL_TEMPLATE = 'carriers/porterbuddy/error_email_template';
    const XML_PATH_ERROR_EMAIL_RECIPIENTS = 'carriers/porterbuddy/error_email_recipients';
    const XML_PATH_ERROR_EMAIL_RECIPIENTS_PORTERBUDDY = 'carriers/porterbuddy/error_email_recipients_porterbuddy';
    const XML_PATH_ERROR_EMAIL_PORTERBUDDY = 'carriers/porterbuddy/error_email_porterbuddy';

    const XML_PATH_MAPS_API_KEY = 'carriers/porterbuddy/maps_api_key';
    const XML_PATH_DEBUG = 'carriers/porterbuddy/debug';

    const XML_PATH_RATES= 'carriers/porterbuddy/rates_table';


    const SHIPMENT_CREATOR_CRON = 'CRON';

    /**
     * @var \Magento\Framework\Locale\Format
     */
    protected $localeFormat;

    /**
     * @var \Magento\Framework\Math\Random
     */
    protected $mathRandom;

    /**
     * @var \Porterbuddy\Porterbuddy\Api\Data\MethodInfoInterfaceFactory
     */
    protected $methodInfoFactory;

    /**
     * @var \Magento\Framework\View\Asset\Repository
     */
    protected  $assetRepo;

    /**
     * @var \Magento\Tax\Helper\Data
     */
    protected $taxHelper;


    public function __construct(
        Context $context,
        \Magento\Framework\Locale\Format $localeFormat,
        \Magento\Framework\Math\Random $mathRandom,
        \Porterbuddy\Porterbuddy\Api\Data\MethodInfoInterfaceFactory $methodInfoFactory,
        \Magento\Framework\View\Asset\Repository $assetRepo,
        \Magento\Tax\Helper\Data $taxHelper
    ) {
        parent::__construct($context);
        $this->localeFormat = $localeFormat;
        $this->mathRandom = $mathRandom;
        $this->methodInfoFactory = $methodInfoFactory;
        $this->assetRepo = $assetRepo;
        $this->taxHelper = $taxHelper;
    }

    /**
     * @param string $websiteCode optional
     * @return bool
     */
    public function getActive($websiteCode = null)
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_ACTIVE, ScopeInterface::SCOPE_WEBSITES, $websiteCode);
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_TITLE, ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return string
     */
    public function getAsapName()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_ASAP_NAME, ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_DESCRIPTION, ScopeInterface::SCOPE_STORE);
    }


    /**
     * @return bool
     */
    public function getAutoCreateShipment()
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_AUTO_CREATE_SHIPMENT, ScopeInterface::SCOPE_WEBSITES);
    }

    /**
     * @return string
     */
    public function getApiMode()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_API_MODE, ScopeInterface::SCOPE_WEBSITES);
    }

    /**
     * @return int
     */
    public function getApiTimeout()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_API_TIMEOUT);
    }

    /**
     * Porterbuddy API URL with regard to selected API mode
     *
     * @return string
     */
    public function getApiUrl()
    {
        switch ($this->getApiMode()) {
            case Carrier::MODE_PRODUCTION:
                return $this->scopeConfig->getValue(self::XML_PATH_PRODUCTION_API_URL, ScopeInterface::SCOPE_WEBSITES);
            default:
                return $this->scopeConfig->getValue(self::XML_PATH_TESTING_API_URL, ScopeInterface::SCOPE_WEBSITES);
        }
    }

    /**
     * Porterbuddy Widget URL
     *
     * @return string
     */
    public function getWidgetUrl()
    {

        return $this->scopeConfig->getValue(self::XML_PATH_WIDGET_URL, ScopeInterface::SCOPE_WEBSITES);

    }

    /**
     * Porterbuddy API key with regard to selected API mode
     *
     * @return string
     */
    public function getApiKey()
    {
        switch ($this->getApiMode()) {
            case Carrier::MODE_PRODUCTION:
                return $this->scopeConfig->getValue(self::XML_PATH_PRODUCTION_API_KEY, ScopeInterface::SCOPE_WEBSITES);
            default:
                return $this->scopeConfig->getValue(self::XML_PATH_TESTING_API_KEY, ScopeInterface::SCOPE_WEBSITES);

        }
    }

    /**
     * Porterbuddy API key with regard to selected API mode
     *
     * @return string
     */
    public function getPublicKey()
    {
        switch ($this->getApiMode()) {
            case Carrier::MODE_PRODUCTION:
                return $this->scopeConfig->getValue(self::XML_PATH_PRODUCTION_PUBLIC_KEY, ScopeInterface::SCOPE_WEBSITES);
            default:
                return $this->scopeConfig->getValue(self::XML_PATH_TESTING_PUBLIC_KEY, ScopeInterface::SCOPE_WEBSITES);

        }
    }

    /**
     * @return string
     */
    public function getOrderEmailIdentity($storeId = null)
    {
        return $this->scopeConfig->getValue(self::XML_PATH_ORDER_EMAIL_IDENTITY, ScopeInterface::SCOPE_STORES, $storeId);
    }

    /**
     * Default phone code
     *
     * @return string
     */
    public function getDefaultPhoneCode()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_DEFAULT_PHONE_CODE, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Packager mode
     *
     * @return string
     */
    public function getPackagerMode()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_PACKAGER_MODE, ScopeInterface::SCOPE_WEBSITES);
    }

    /**
     * Inventory Stock
     *
     * @return string
     */
    public function getInventoryStock()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_INVENTORY_STOCK, ScopeInterface::SCOPE_STORE);

    }

    /**
     * Returns packing time
     *
     * @return float
     */
    public function getPackingTime()
    {
        return (float)$this->scopeConfig->getValue(self::XML_PATH_PACKING_TIME, ScopeInterface::SCOPE_WEBSITES);
    }

    /**
     * Returns use donor
     *
     * @return bool
     */
    public function getUseDonor()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_USE_DONOR, ScopeInterface::SCOPE_WEBSITES);
    }

    /**
     * Inbound access token for requests from Porterbuddy
     *
     * @return string
     */
    public function getInboundToken()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_INBOUND_TOKEN, ScopeInterface::SCOPE_WEBSITES);
    }

    /**
     * @return string
     */
    public function showAvailability()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_SHOW_AVAILABILITY, ScopeInterface::SCOPE_STORE);
    }


    /**
     * @return boolean
     */
    public function getAvailabilityAlternateView()
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_AVAILABILITY_ALTERNATE_VIEW, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Delivery availability template text
     *
     * @return string
     */
    public function getLocationLinkTemplate()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_LOCATION_LINK_TEMPLATE, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Delivery availability template text
     *
     * @return string
     */
    public function getAvailabilityTemplate()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_AVAILABILITY_TEMPLATE, ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return string
     */
    public function getAvailabilityTextFetching()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_AVAILABILITY_TEXT_FETCHING, ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return string
     */
    public function getAvailabilityTextClickToSee()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_AVAILABILITY_TEXT_CLICK_TO_SEE, ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return string
     */
    public function getAvailabilityTextPostcodeError()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_AVAILABILITY_TEXT_POSTCODE_ERROR, ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return string
     */
    public function getAvailabilityTextOutOfStock()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_AVAILABILITY_TEXT_OUT_OF_STOCK, ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return string
     */
    public function getAvailabilityTextNoDate()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_AVAILABILITY_TEXT_NO_DATE, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Whether verbose log is enabled
     *
     * @return bool
     */
    public function getDebug()
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_DEBUG, ScopeInterface::SCOPE_STORE);
    }

    /**
     * @param string $dayOfWeek
     * @return string[]|bool - ['open', 'close'], false if not working
     * @throws Exception
     */
    public function getOpenHours($dayOfWeek)
    {
        $map = [
            'mon' => self::XML_PATH_HOURS_MON,
            'tue' => self::XML_PATH_HOURS_TUE,
            'wed' => self::XML_PATH_HOURS_WED,
            'thu' => self::XML_PATH_HOURS_THU,
            'fri' => self::XML_PATH_HOURS_FRI,
            'sat' => self::XML_PATH_HOURS_SAT,
            'sun' => self::XML_PATH_HOURS_SUN,
        ];
        if (!isset($map[$dayOfWeek])) {
            throw new Exception(__('Incorrect day of week `%1`.', $dayOfWeek));
        }

        $value = $this->scopeConfig->getValue($map[$dayOfWeek], ScopeInterface::SCOPE_WEBSITES);
        $parts = explode(',', $value);

        if (2 !== count($parts)) {
            // misconfig, not working
            return false;
        }
        return [
            'open' => $parts[0],
            'close' => $parts[1],
        ];
    }

    /**
     * @param string $dayOfWeek optional
     * @return int
     * @throws Exception
     */
    public function getPorterbuddyUntil($dayOfWeek = null)
    {
        $default = $this->scopeConfig->getValue(self::XML_PATH_PORTERBUDDY_UNTIL, ScopeInterface::SCOPE_WEBSITES);
        if (!$dayOfWeek) {
            return (int)$default;
        }

        $map = [
            'mon' => self::XML_PATH_PORTERBUDDY_UNTIL_MON,
            'tue' => self::XML_PATH_PORTERBUDDY_UNTIL_TUE,
            'wed' => self::XML_PATH_PORTERBUDDY_UNTIL_WED,
            'thu' => self::XML_PATH_PORTERBUDDY_UNTIL_THU,
            'fri' => self::XML_PATH_PORTERBUDDY_UNTIL_FRI,
            'sat' => self::XML_PATH_PORTERBUDDY_UNTIL_SAT,
            'sun' => self::XML_PATH_PORTERBUDDY_UNTIL_SUN,
        ];
        if (!isset($map[$dayOfWeek])) {
            throw new Exception(__('Incorrect day of week `%1`.', $dayOfWeek));
        }

        $value = $this->scopeConfig->getValue($map[$dayOfWeek], ScopeInterface::SCOPE_WEBSITES);
        if (strlen($value)) {
            return (int)$value;
        }

        return (int)$default;
    }

    /**
     * @return bool
     */
    public function isRequireSignatureDefault()
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_REQUIRE_SIGNATURE_DEFAULT, ScopeInterface::SCOPE_WEBSITES);
    }

    /**
     * @return int
     */
    public function getMinAgeCheckDefault()
    {
        return (int)$this->scopeConfig->getValue(self::XML_PATH_MIN_AGE_CHECK_DEFAULT, ScopeInterface::SCOPE_WEBSITES);
    }

    /**
     * @return bool
     */
    public function isIdCheckDefault()
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_ID_CHECK_DEFAULT, ScopeInterface::SCOPE_WEBSITES);
    }

    /**
     * @return bool
     */
    public function isOnlyToRecipientDefault()
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_ONLY_RECIPIENT_DEFAULT, ScopeInterface::SCOPE_WEBSITES);
    }

    /**
     * @return int|null
     */
    public function getRequireSignatureAttr()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_REQUIRE_SIGNATURE_ATTR, ScopeInterface::SCOPE_WEBSITES);
    }

    /**
     * @return int|null
     */
    public function getMinAgeCheckAttr()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_MIN_AGE_CHECK_ATTR, ScopeInterface::SCOPE_WEBSITES);
    }

    /**
     * @return int|null
     */
    public function getIdCheckAttr()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_ID_CHECK_ATTR, ScopeInterface::SCOPE_WEBSITES);
    }

    /**
     * @return int|null
     */
    public function getOnlyToRecipientAttr()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_ONLY_RECIPIENT_ATTR, ScopeInterface::SCOPE_WEBSITES);
    }

    /**
     * @return bool
     */
    public function availabilityAutoUpdateComposite()
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_AVAILABILITY_AUTO_UPDATE_COMPOSITE, ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return string
     */
    public function getWeightUnit()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_WEIGHT_UNIT, ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return string
     */
    public function getDimensionUnit()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_DIMENSION_UNIT, ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return float
     */
    public function getDefaultProductWeight()
    {
        return (float)trim($this->scopeConfig->getValue(self::XML_PATH_DEFAULT_PRODUCT_WEIGHT, ScopeInterface::SCOPE_STORE));
    }

    /**
     * @return float
     */
    public function getDefaultProductHeight()
    {
        return (float)trim($this->scopeConfig->getValue(self::XML_PATH_DEFAULT_PRODUCT_HEIGHT, ScopeInterface::SCOPE_STORE));
    }

    /**
     * @return float
     */
    public function getDefaultProductWidth()
    {
        return (float)trim($this->scopeConfig->getValue(self::XML_PATH_DEFAULT_PRODUCT_WIDTH, ScopeInterface::SCOPE_STORE));
    }

    /**
     * @return float
     */
    public function getDefaultProductLength()
    {
        return (float)trim($this->scopeConfig->getValue(self::XML_PATH_DEFAULT_PRODUCT_LENGTH, ScopeInterface::SCOPE_STORE));
    }

    /**
     * @return string
     */
    public function getHeightAttribute()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_HEIGHT_ATTRIBUTE, ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return string
     */
    public function getWidthAttribute()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_WIDTH_ATTRIBUTE, ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return string
     */
    public function getLengthAttribute()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_LENGTH_ATTRIBUTE, ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return int
     */
    public function getDaysAhead()
    {
        return (int)$this->scopeConfig->getValue(self::XML_PATH_DAYS_AHEAD, ScopeInterface::SCOPE_WEBSITES);
    }

    /**
     * @return int
     */
    public function getExtraPickupWindows()
    {
        return (int)$this->scopeConfig->getValue(self::XML_PATH_EXTRA_PICKUP_WINDOWS, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Overriden price for express delivery, in base currency
     *
     * @return float|null
     */
    public function getPriceOverrideExpress()
    {
        $value = $this->scopeConfig->getValue(self::XML_PATH_PRICE_OVERRIDE_EXPRESS, ScopeInterface::SCOPE_WEBSITES);
        if (!strlen($value)) {
            return null;
        }

        return $this->localeFormat->getNumber($value);
    }

    /**
     * Overriden price for express delivery, in base currency
     *
     * @return float|null
     */
    public function getPriceOverrideExpressReturn()
    {
        $value = $this->scopeConfig->getValue(self::XML_PATH_PRICE_OVERRIDE_EXPRESS_RETURN, ScopeInterface::SCOPE_WEBSITES);
        if (!strlen($value)) {
            return null;
        }

        return $this->localeFormat->getNumber($value);
    }

    /**
     * Overriden price for normal delivery, in base currency
     *
     * @return float|null
     */
    public function getPriceOverrideDelivery()
    {
        $value = $this->scopeConfig->getValue(self::XML_PATH_PRICE_OVERRIDE_DELIVERY, ScopeInterface::SCOPE_WEBSITES);
        if (!strlen($value)) {
            return null;
        }

        return $this->localeFormat->getNumber($value);
    }

    /**
     * Overriden price for normal delivery, in base currency
     *
     * @return float|null
     */
    public function getPriceOverrideDeliveryReturn()
    {
        $value = $this->scopeConfig->getValue(self::XML_PATH_PRICE_OVERRIDE_DELIVERY_RETURN, ScopeInterface::SCOPE_WEBSITES);
        if (!strlen($value)) {
            return null;
        }

        return $this->localeFormat->getNumber($value);
    }

    /**
     * @return array
     */
    public function getDiscounts()
    {
        $discounts = [];
        $value = $this->scopeConfig->getValue(
            self::XML_PATH_DISCOUNTS,
            ScopeInterface::SCOPE_WEBSITES
        );
        $rows = $this->unserialize($value);
        foreach ((array)$rows as $row) {
            if (isset($row['discount'])) {
                $discounts[] = (array)$row;
            }
        }
        return $discounts;
    }

    /**
     * @return bool
     */
    public function getReturnEnabled()
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_RETURN_ENABLED, ScopeInterface::SCOPE_WEBSITES);
    }

    /**
     * @return bool
     */
    public function isPreselectLocation()
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_PRESELECT_LOCATION, ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return string
     */
    public function getCheckoutWidgetTitle()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_CHECKOUT_WIDGET_TITLE, ScopeInterface::SCOPE_STORE);
    }
    /**
     * @return string
     */
    public function getEnterPostcodeText()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_ENTER_POSTCODE_TEXT, ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return string
     */
    public function getHomeDeliveryTitle()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_HOME_DELIVERY_TITLE, ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return string
     */
    public function getPickupPointTitle()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_PICKUP_POINT_TITLE, ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return string
     */
    public function getCollectInStoreTitle()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_COLLECT_IN_STORE_TITLE, ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return string
     */
    public function getReturnText()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_RETURN_TEXT, ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return string
     */
    public function getReturnShortText()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_RETURN_SHORT_TEXT, ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return string
     */
    public function getAddressWarningText()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_ADDRESS_WARNING_TEXT, ScopeInterface::SCOPE_STORE);
    }
    /**
     * @return string
     */
    public function getAddressWarningTitle()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_ADDRESS_WARNING_TITLE, ScopeInterface::SCOPE_STORE);
    }
    /**
     * @return string
     */
    public function getAddressWarningCloseLabel()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_ADDRESS_WARNING_CLOSE_LABEL, ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return float
     */
    public function getRefreshOptionsTimeout()
    {
        $value = $this->scopeConfig->getValue(self::XML_PATH_REFRESH_OPTIONS_TIMEOUT, ScopeInterface::SCOPE_WEBSITES);
        if (!strlen($value)) {
            return 0;
        }

        return $this->localeFormat->getNumber($value);
    }

    /**
     * @return bool
     */
    public function isLeaveDoorstepEnabled()
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_LEAVE_DOORSTEP_ENABLED, ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return string
     */
    public function getLeaveDoorstepText()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_LEAVE_DOORSTEP_TEXT, ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return string
     */
    public function getCommentText()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_COMMENT_TEXT, ScopeInterface::SCOPE_STORE);
    }


    /**
     * @return string
     */
    public function getHideShippingOnEmptyAddress()
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_HIDE_SHIPPING_EMPTY_ADDRESS, ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return bool
     */
    public function getErrorEmailEnabled($storeId = null)
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_ERROR_EMAIL_ENABLED, ScopeInterface::SCOPE_STORES, $storeId);
    }

    /**
     * @return string
     */
    public function getErrorEmailIdentify($storeId = null)
    {
        return $this->scopeConfig->getValue(self::XML_PATH_ERROR_EMAIL_IDENTITY, ScopeInterface::SCOPE_STORES, $storeId);
    }

    /**
     * @return string
     */
    public function getErrorEmailTemplate($storeId = null)
    {
        return $this->scopeConfig->getValue(self::XML_PATH_ERROR_EMAIL_TEMPLATE, ScopeInterface::SCOPE_STORES, $storeId);
    }

    /**
     * @return array
     */
    public function getErrorEmailRecipients()
    {
        $emails = [];
        $value = $this->scopeConfig->getValue(
            self::XML_PATH_ERROR_EMAIL_RECIPIENTS,
            ScopeInterface::SCOPE_WEBSITES
        );
        $rows = $this->unserialize($value);
        foreach ((array)$rows as $row) {
            if (isset($row['email'])) {
                $emails[] = trim($row['email']);
            }
        }
        return $emails;
    }

    /**
     * @return array
     */
    public function getErrorEmailRecipientsPorterbuddy()
    {
        $emails = [];
        $value = $this->scopeConfig->getValue(
            self::XML_PATH_ERROR_EMAIL_RECIPIENTS_PORTERBUDDY,
            ScopeInterface::SCOPE_WEBSITES
        );
        $rows = $this->unserialize($value);
        foreach ((array)$rows as $row) {
            if (isset($row['email'])) {
                $emails[] = trim($row['email']);
            }
        }
        return $emails;
    }

    /**
     * Porterbuddy email that is always in the email list
     *
     * @return string
     */
    public function getErrorEmailPorterbuddy()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_ERROR_EMAIL_PORTERBUDDY);
    }

    /**
     * Maps API Key
     *
     * @return string
     */
    public function getMapsApiKey()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_MAPS_API_KEY, ScopeInterface::SCOPE_STORE);
    }


    /**
     * Discounts
     *
     * @return array
     */
    public function getRates()
    {
        $rates = array();
        $configRates = $this->scopeConfig->getValue(self::XML_PATH_RATES, ScopeInterface::SCOPE_STORE);
        $configRates = $this->unserialize($configRates);
        foreach ((array)$configRates as $row) {
            if(isset($row['carrier_code'])){
                $_thisRow = (array)$row;
                if(isset($_thisRow['logo_url']) && strlen($_thisRow['logo_url']) > 0 && strpos($_thisRow['logo_url'], 'http', 0) !== 0){
                    $_thisRow['logo_url'] =  $this->assetRepo->getUrl($_thisRow['logo_url']);
                }
                $rates[] = $_thisRow;

            }
        }

        return $rates;
    }


    // END CONFIG SECTION

    /**
     * Convert Weight to KILOGRAM
     *
     * @param float|null $weight
     * @param string|null $weightUnit optional, by default from config
     * @return float|null
     * @throws Exception
     */
    public function convertWeightToGrams($weight, $weightUnit = null)
    {
        if (!strlen($weight)) {
            return null;
        }

        if (!is_numeric($weight)) {
            throw new Exception(__('Weight must be numeric, `%1` given.', $weight));
        }

        if (!$weightUnit) {
            $weightUnit = $this->getWeightUnit();
        }

        switch ($weightUnit) {
            case Carrier::WEIGHT_KILOGRAM:
                return $weight*1000;
            case Carrier::WEIGHT_GRAM:
                return (float)$weight;
            default:
                throw new Exception(__('Invalid weight unit `%1`.', $weightUnit));
        }
    }

    /**
     * Convert Dimension to MILLIMETER
     *
     * @param float|null $dimension
     * @return float|null
     * @throws Exception
     */
    public function convertDimensionToCm($dimension, $dimensionUnit = null)
    {
        if (!strlen($dimension)) {
            return null;
        }

        if (!is_numeric($dimension)) {
            throw new Exception(__('Dimension must be numeric, `%1` given.', $dimension));
        }

        if (!$dimensionUnit) {
            $dimensionUnit = $this->getDimensionUnit();
        }

        switch ($dimensionUnit) {
            case Carrier::UNIT_MILLIMETER:
                return $dimension/10;
            case Carrier::UNIT_CENTIMETER:
                return $dimension;
            default:
                throw new Exception(__('Invalid dimension unit `%1`.', $dimensionUnit));
        }
    }

    /**
     * Formats human label from path like field_one.field_two -> Field One - Field Two, translates each part
     *
     * @param $path
     * @return string
     */
    public function formatLabel($path)
    {
        // make label, translate each part
        $parts = explode('.', $path);
        $label = implode(' - ', array_map(function ($label) {
            $label = str_replace('_', ' ', $label);
            $label = str_replace('.', ' - ', $label);
            $label = ucfirst($label);
            return __($label);
        }, $parts));

        return $label;
    }

    /**
     * Separates out phone code from number
     *
     * @param string $phone
     * @return array
     */
    public function splitPhoneCodeNumber($phone)
    {
        $phone = str_replace(' ', '', $phone);
        $phone = trim($phone);

        if (!strlen($phone)) {
            return ['', ''];
        }

        // +47 12 34 56 => (+47) (123456)
        if (preg_match('/^\+(\d{2})(.+)/', $phone, $matches)) {
            $code = $matches[1];
            $number = trim($matches[2]);
            return ["+$code", $number];
        }

        // nothing matched
        return ['', $phone];
    }

    /**
     * Packs timeslot into Magento shipping method code
     *
     * Example: er_180830+02:00_1000_1200
     * Express with return
     * start 2018-08-30 10:00 +02:00
     * end 2018-08-30 12:00 +02:00
     *
     * @param array $option
     * @param bool $skipDate optional for selecting timeslot later in confirmation
     * @return string
     * @throws Exception
     */
    public function makeMethodCode(array $option, $skipDate = false)
    {
        switch ($option['product']) {
            case Carrier::METHOD_EXPRESS:
                $type = Carrier::SHORTCUT_EXPRESS;
                break;
            case Carrier::METHOD_DELIVERY:
                $type = Carrier::SHORTCUT_DELIVERY;
                break;
            case Carrier::METHOD_EXPRESS_RETURN:
                $type = Carrier::SHORTCUT_EXPRESS_RETURN;
                break;
            case Carrier::METHOD_DELIVERY_RETURN:
                $type = Carrier::SHORTCUT_DELIVERY_RETURN;
                break;
            default:
                throw new Exception(__('Unknown product `%1`.', $option['product']));
        }
        if ($skipDate) {
            return $type;
        }

        if(!isSet($option['start'])){
            return "{$type}_" . Carrier::CONSOLIDATION_FLAG;
        }
        $start = new \DateTime($option['start']);
        $end = new \DateTime($option['end']);

        $date = $start->format('ymdP');
        $start = $start->format('Hi');
        $end = $end->format('Hi');

        return "{$type}_{$date}_{$start}_{$end}";
    }

    /**
     * @param string $methodCode
     * @return MethodInfoInterface
     * @throws Exception
     */
    public function parseMethod($methodCode)
    {
        /** @var MethodInfoInterface $result */
        $result = $this->methodInfoFactory->create();

        $parts = explode('_', $methodCode);
        $type = array_shift($parts);
        if (Carrier::CODE == $type) {
            // skip carrier code if present
            $type = array_shift($parts);
        }

        if (strlen($type) > 2) {
            $this->parseLongFormat($result, $type, $parts);
        } else {
            $this->parseNewFormat($result, $type, $parts);
        }

        return $result;
    }

    /**
     * Parses deprecated longer format, up to 80 characters
     *
     * Example: express-with-return_2018-05-11T13:00:00+00:00_2018-05-11T15:00:00+00:00
     *
     * @param MethodInfoInterface $result
     * @param string $type
     * @param array $parts
     */
    protected function parseLongFormat(MethodInfoInterface $result, $type, array $parts)
    {
        // product code as returned from API
        $result->setProduct($type);

        $pos = strpos($type, '-with-return');
        if ($pos) {
            $result->setType(substr($type, 0, $pos));
            $result->setReturn(true);
        } else {
            $result->setType($type);
            $result->setReturn(false);
        }

        if ($parts) {
            $result->setStart(array_shift($parts));
            $result->setEnd(array_shift($parts));
        }
    }

    /**
     * Parses bew shorter format, fit 40 characters
     *
     * Example: er_180830+02:00_1000_1200
     *
     * @param MethodInfoInterface $result
     * @param string $type
     * @param array $parts
     * @throws Exception
     */
    protected function parseNewFormat(MethodInfoInterface $result, $type, array $parts)
    {
        // d, x, dr, xr
        switch ($type) {
            case Carrier::SHORTCUT_DELIVERY:
                $result->setProduct(Carrier::METHOD_DELIVERY);
                $result->setType(Carrier::METHOD_DELIVERY);
                $result->setReturn(false);
                break;
            case Carrier::SHORTCUT_DELIVERY_RETURN:
                $result->setProduct(Carrier::METHOD_DELIVERY_RETURN);
                $result->setType(Carrier::METHOD_DELIVERY);
                $result->setReturn(true);
                break;
            case Carrier::SHORTCUT_EXPRESS:
                $result->setProduct(Carrier::METHOD_EXPRESS);
                $result->setType(Carrier::METHOD_EXPRESS);
                $result->setReturn(false);
                break;
            case Carrier::SHORTCUT_EXPRESS_RETURN:
                $result->setProduct(Carrier::METHOD_EXPRESS_RETURN);
                $result->setType(Carrier::METHOD_EXPRESS);
                $result->setReturn(true);
                break;
            default:
                throw new Exception(__('Unknown method type `%1`.', $type));
        }

        if ($parts) {
            $firstElement = array_shift($parts);
            if($firstElement == Carrier::CONSOLIDATION_FLAG){
                $result->setConsolidated(true);
            }
            else {
                $date = $firstElement;

                $timeStart = array_shift($parts);
                $timeEnd = array_shift($parts);

                if ($date && $timeStart && $timeEnd) {
                    // 180830+02:00 1000
                    $start = \DateTime::createFromFormat('ymdP Hi', "$date $timeStart");
                    $end = \DateTime::createFromFormat('ymdP Hi', "$date $timeEnd");

                    $result->setStart($start->format(\DateTime::ATOM));
                    $result->setEnd($end->format(\DateTime::ATOM));
                }
            }
        }
    }

    public function formatApiDateTime($dateTime)
    {
        if (null === $dateTime) {
            return null;
        }
        if (is_string($dateTime)) {
            $dateTime = new \DateTime($dateTime);
        }

        $dateTime->setTimezone($this->getTimezone());

        return $dateTime->format(static::API_DATE_FORMAT);
    }

    /**
     * Current time in UTC timezone
     *
     * @return \DateTime
     */
    public function getCurrentTime()
    {
        return new \DateTime();
    }

    /**
     * Returns local timezone
     *
     * @return \DateTimeZone
     */
    public function getTimezone()
    {
        $configTimezone = $this->scopeConfig->getValue(
            \Magento\Directory\Helper\Data::XML_PATH_DEFAULT_TIMEZONE,
            ScopeInterface::SCOPE_STORE
        );
        return new \DateTimeZone($configTimezone);
    }

    public function formatPrice(\Magento\Quote\Model\Quote $quote, $price)
    {
        $shippingPrice = $this->taxHelper->getShippingPrice(
            $price,
            $this->taxHelper->displayShippingPriceIncludingTax(),
            $quote->getShippingAddress()
        );
        // FIXME
        $convertedPrice = $quote->getStore()->getCurrentCurrency()->convert($shippingPrice);
        return $convertedPrice;
    }

    /**
     * Converts {{...}} and [[...]] to <%=...%> placeholders, optionally wraps each
     *
     * @param string $template
     * @param string $wrapper optional
     * @return string
     */
    public function processPlaceholders(
        $template,
        $wrapper = '<span class="porterbuddy-availability-{{name}}">{{value}}</span>'
    ) {
        $callback = function ($matches) use ($wrapper) {
            $name = $matches[1];
            $value = '<%=' . $name . '%>';
            if ($wrapper) {
                $value = str_replace(['{{name}}', '{{value}}'], [$name, $value], $wrapper);
            }
            return $value;
        };

        $template = preg_replace_callback('/{{(.+)}}/U', $callback, $template);
        $template = preg_replace_callback('/\[\[(.+)\]\]/U', $callback, $template);

        return $template;
    }

    /**
     * @param string $value
     * @return mixed
     */
    public function unserialize($value)
    {
        // Magento 2.2+
        $result = @json_decode($value, true);
        if (null === $result && JSON_ERROR_NONE !== json_last_error()) {
            // Magento < 2.2
            $result = @unserialize($result);
        }

        return $result;
    }

    /**
     * Replaces characters with * keeping only initial and last few symbols
     *
     * Example: 1234******cdef
     *
     * @param string $value
     * @param int $keepLength
     * @return string
     */
    public function obscureValue($value, $keepLength = 4)
    {
        if (strlen($value) > $keepLength*2) {
            $value = substr($value, 0, $keepLength)
                . str_repeat('*', strlen($value)-2*$keepLength)
                . substr($value, -$keepLength);
        }

        return $value;
    }
}
