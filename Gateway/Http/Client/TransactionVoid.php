<?php

declare(strict_types=1);

namespace Worldline\PaymentCore\Gateway\Http\Client;

use Magento\Framework\Exception\LocalizedException;
use OnlinePayments\Sdk\DataObject;
use Psr\Log\LoggerInterface;
use Worldline\PaymentCore\Api\Service\Payment\CancelPaymentServiceInterface;
use Worldline\PaymentCore\Api\Service\Payment\GetPaymentServiceInterface;
use Worldline\PaymentCore\Gateway\Request\VoidAndCancelDataBuilder;

/**
 * Process gateway void action
 */
class TransactionVoid extends AbstractTransaction
{
    /**
     * @var GetPaymentServiceInterface
     */
    private $getPaymentService;

    /**
     * @var CancelPaymentServiceInterface
     */
    private $cancelPaymentService;

    public function __construct(
        LoggerInterface $logger,
        GetPaymentServiceInterface $getPaymentService,
        CancelPaymentServiceInterface $cancelPaymentService
    ) {
        parent::__construct($logger);
        $this->getPaymentService = $getPaymentService;
        $this->cancelPaymentService = $cancelPaymentService;
    }

    /**
     * Process gateway void action
     *
     * @param array $data
     * @return DataObject
     * @throws LocalizedException
     */
    protected function process(array $data): DataObject
    {
        $payment = $this->getPaymentService->execute(
            $data[VoidAndCancelDataBuilder::TRANSACTION_ID],
            $data[VoidAndCancelDataBuilder::STORE_ID]
        );

        if (!$payment->getStatusOutput()->getIsCancellable()) {
            throw new LocalizedException(__('Void action can not be performed.'));
        }

        return $this->cancelPaymentService->execute(
            $data[VoidAndCancelDataBuilder::TRANSACTION_ID],
            $data[VoidAndCancelDataBuilder::STORE_ID]
        );
    }
}
