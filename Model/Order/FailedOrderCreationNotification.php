<?php
declare(strict_types=1);

namespace Worldline\PaymentCore\Model\Order;

use Magento\Framework\App\Area;
use Magento\Framework\Exception\MailException;
use Magento\Framework\Mail\Template\SenderResolverInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Worldline\PaymentCore\Api\Data\PaymentInterface;
use Worldline\PaymentCore\Model\Config\OrderNotificationConfigProvider;
use Worldline\PaymentCore\Model\EmailSender;
use Worldline\PaymentCore\Model\ResourceModel\Quote as QuoteResource;

class FailedOrderCreationNotification
{
    public const WEBHOOK_SPACE = 'webhook';
    public const WAITING_PAGE_SPACE = 'waiting page';
    public const CRON_SPACE = 'cron';

    /**
     * @var EmailSender
     */
    private $emailSender;

    /**
     * @var OrderNotificationConfigProvider
     */
    private $orderNotificationConfigProvider;

    /**
     * @var SenderResolverInterface
     */
    private $senderResolver;

    /**
     * @var DateTime
     */
    private $dateTime;

    /**
     * @var QuoteResource
     */
    private $quoteResource;

    public function __construct(
        EmailSender $emailSender,
        OrderNotificationConfigProvider $orderNotificationConfigProvider,
        SenderResolverInterface $senderResolver,
        DateTime $dateTime,
        QuoteResource $quoteResource
    ) {
        $this->emailSender = $emailSender;
        $this->orderNotificationConfigProvider = $orderNotificationConfigProvider;
        $this->senderResolver = $senderResolver;
        $this->dateTime = $dateTime;
        $this->quoteResource = $quoteResource;
    }

    /**
     * @param string $incrementId
     * @param string $errorMessage
     * @param string $space
     * @return void
     * @throws MailException
     */
    public function notify(string $incrementId, string $errorMessage, string $space): void
    {
        if (!$this->orderNotificationConfigProvider->isEnabled()) {
            return;
        }

        $recipient = $this->senderResolver->resolve($this->orderNotificationConfigProvider->getRecipient());
        $this->emailSender->sendEmail(
            $this->orderNotificationConfigProvider->getEmailTemplate(),
            0,
            $this->orderNotificationConfigProvider->getSender(),
            $recipient['email'] ?? '',
            $this->getVariables($incrementId, $errorMessage, $space),
            ['area' => Area::AREA_ADMINHTML, 'store' => 0]
        );
    }

    private function getVariables(string $incrementId, string $errorMessage, string $space): array
    {
        $quote = $this->quoteResource->getQuoteByReservedOrderId($incrementId);

        return [
            'store_id' => $quote->getStoreId(),
            'reserved_order_id' => $incrementId,
            'wl_payment_id' => $quote->getPayment()->getAdditionalInformation(PaymentInterface::PAYMENT_ID),
            'customer_email' => $quote->getCustomerEmail(),
            'date' => date('Y-m-d H:i:s', $this->dateTime->gmtTimestamp()),
            'error_message' => $errorMessage,
            'space' => $space,
        ];
    }
}
