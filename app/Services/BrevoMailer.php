<?php

namespace App\Services;

use SendinBlue\Client\Configuration;
use SendinBlue\Client\Api\TransactionalEmailsApi;
use GuzzleHttp\Client;

class BrevoMailer
{
    protected $apiInstance;

    public function __construct()
    {
        $config = Configuration::getDefaultConfiguration()->setApiKey('api-key', env('BREVO_API_KEY'));
        $this->apiInstance = new TransactionalEmailsApi(new Client(), $config);
    }

    public function sendMail($toEmail, $toName, $subject, $htmlContent, $fromEmail, $fromName)
    {
        $sendSmtpEmail = [
            'to' => [['email' => $toEmail, 'name' => $toName]],
            'sender' => ['email' => $fromEmail, 'name' => $fromName],
            'subject' => $subject,
            'htmlContent' => $htmlContent
        ];

        return $this->apiInstance->sendTransacEmail($sendSmtpEmail);
    }
}
