<?php
/**
 * @author Convert Team
 * @copyright Copyright (c) 2018 Convert (http://www.convert.no/)
 */
namespace Porterbuddy\Porterbuddy\Controller\Delivery;

use Magento\Framework\Exception\LocalizedException;

class Options extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Framework\Data\Form\FormKey\Validator
     */
    protected $formKeyValidator;

    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $session;
    /**
     * @var \Magento\Quote\Model\QuoteRepository
     */
    protected $quoteRepository;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Magento\Checkout\Model\Session $session
     * @param \Magento\Quote\Model\QuoteRepository $quoteRepository
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Checkout\Model\Session $session,
        \Magento\Quote\Model\QuoteRepository $quoteRepository
    ) {
        $this->formKeyValidator = $formKeyValidator;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->session = $session;
        $this->quoteRepository = $quoteRepository;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Json $result */
        $result = $this->resultJsonFactory->create();

        if (!$this->getRequest()->isPost()) {
            return $result->setData([
                'errors' => true,
                'message' => __('Method not allowed'),
            ]);
        }
        if (!$this->formKeyValidator->validate($this->getRequest())) {
            return $result->setData([
                'errors' => true,
                'message' => __('Invalid Form Key. Please refresh the page.'),
            ]);
        }

        $resultData = [];
        $type = $this->getRequest()->getPost('type');
        $quote = $this->session->getQuote();
        if($type == 'comment'){
            $resultData['type'] = 'comment';
            $comment = $this->getRequest()->getPost('comment');
            $resultData['value'] = $comment;

            $quote->setPbComment($comment);
        }elseif($type == 'doorstep'){
            $resultData['type'] = 'doorstep';
            $doorstep =  $this->getRequest()->getPost('leave_doorstep');
            if($doorstep == 'true'){
                $resultData['value'] = 'true';
                $quote->setPbLeaveDoorstep(1);
            }else{
                $resultData['value'] = 'false';
                $quote->setPbLeaveDoorstep(0);
            }
        }

        try {
            $this->quoteRepository->save($quote);
        } catch (LocalizedException $e) {
            return $result->setData([
                'errors' => true,
                'message' => $e->getMessage(),
            ]);
        } catch (\Exception $e) {
            return $result->setData([
                'errors' => true,
                'message' => __('An error occurred when updating cart'),
            ]);
        }
        $resultData['setDoorstep'] = $quote->getPbLeaveDoorsep();
        $resultData['comment'] = $quote->getPbComment();
        $resultData['errors'] = false;
        return $result->setData($resultData);
    }
}
