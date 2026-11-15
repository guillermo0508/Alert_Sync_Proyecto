<?php

namespace App\Mail\Transport;

use Illuminate\Support\Facades\Http;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\MessageConverter;

/**
 * Envía los correos al servicio `mail-relay` (Vercel), que los manda por SMTP de Gmail.
 * Se usa porque el plan gratuito de Render bloquea los puertos SMTP salientes.
 */
class HttpRelayTransport extends AbstractTransport
{
    public function __construct(
        private readonly string $url,
        private readonly string $secret,
        private readonly int $timeout = 20,
    ) {
        parent::__construct();
    }

    protected function doSend(SentMessage $message): void
    {
        $email = MessageConverter::toEmail($message->getOriginalMessage());
        $addresses = fn (array $list) => array_map(fn (Address $a) => $a->toString(), $list);

        $response = Http::withToken($this->secret)
            ->acceptJson()
            ->timeout($this->timeout)
            ->retry(2, 1500, throw: false)
            ->post($this->url, [
                'to' => $addresses($email->getTo()),
                'cc' => $addresses($email->getCc()),
                'bcc' => $addresses($email->getBcc()),
                'reply_to' => $email->getReplyTo() ? $email->getReplyTo()[0]->toString() : null,
                'from_name' => $email->getFrom() ? $email->getFrom()[0]->getName() : null,
                'subject' => $email->getSubject(),
                'html' => $email->getHtmlBody(),
                'text' => $email->getTextBody(),
            ]);

        if (! $response->successful()) {
            throw new TransportException(
                'El servicio de correo respondió ' . $response->status() . ': ' . $response->body()
            );
        }
    }

    public function __toString(): string
    {
        return 'relay+https://' . parse_url($this->url, PHP_URL_HOST);
    }
}
