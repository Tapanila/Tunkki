<?php

declare(strict_types=1);

namespace App\Controller;

use Sonata\AdminBundle\Controller\CRUDController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\HttpFoundation\RedirectResponse;

final class EmailAdminController extends CRUDController
{
    public function previewAction(): Response
    {
        $email = $this->admin->getSubject();
        $event = $email->getEvent();
        $img = null;
        if (!is_null($event)) {
            $img = $event->getPicture();
        }
        $admin = $this->admin;
        return $this->renderWithExtraParams('emails/email.html.twig', [
            'body' => $email->getBody(),
            'email' => $email,
            'admin' => $admin,
            'img' => $img
        ]);
    }
    public function sendAction(MailerInterface $mailer): RedirectResponse
    {
        $email = $this->admin->getSubject();
        $links = $email->getAddLoginLinksToFooter();
        $purpose = $email->getPurpose();
        $subject = $email->getSubject();
        $event = $email->getEvent();
        $body = $email->getBody();
        $count = 0;
        $replyto = $email->getReplyTo() ?: 'hallitus@entropy.fi';
        if ($subject && $body && $event) {
            if ($purpose == 'rsvp') {
                $rsvps = $event->getRSVPs();
                if ((is_countable($rsvps) ? count($rsvps) : 0) > 0) {
                    foreach ($rsvps as $rsvp) {
                        $to = $rsvp->getAvailableEmail();
                        $message = $this->generateMail($to, $replyto, $subject, $body, $links, $event->getPicture());
                        $mailer->send($message);
                        $count += 1;
                    }
                }
            } elseif ($purpose == 'ticket') {
                $tickets = $event->getTickets();
                foreach ($tickets as $ticket) {
                    if (str_starts_with($ticket->getStatus(), 'paid') || $ticket->getStatus() == 'reserved') {
                        $to = $ticket->getOwnerEmail();
                        if ($to) {
                            $message = $this->generateMail($to, $replyto, $subject, $body, $links, $event->getPicture());
                            $mailer->send($message);
                            $count += 1;
                        }
                    }
                }
            } elseif ($purpose == 'nakkikone') {
                $nakkis = $event->getNakkiBookings();
                $emails = [];
                foreach ($nakkis as $nakki) {
                    $member = $nakki->getMember();
                    if ($member) {
                        $emails[$member->getId()] = $member->getEmail();
                    }
                }
                foreach ($emails as $to) {
                    if ($to) {
                        $message = $this->generateMail($to, $replyto, $subject, $body, $links, $event->getPicture());
                        $mailer->send($message);
                        $count += 1;
                    }
                }
            } elseif ($purpose == 'artist') {
                $signups = $event->getEventArtistInfos();
                $emails = [];
                foreach ($signups as $signup) {
                    $member = $signup->getArtist()->getMember();
                    if ($member) {
                        $emails[$member->getId()] = $member->getEmail();
                    }
                }
                foreach ($emails as $to) {
                    if ($to) {
                        $message = $this->generateMail($to, $replyto, $subject, $body, $links, $event->getPicture());
                        $mailer->send($message);
                        $count += 1;
                    }
                }
            } elseif ($purpose == 'aktiivit') {
                $to = 'aktiivit@entropy.fi';
                $message = $this->generateMail($to, $replyto, $subject, $body, $links, $event->getPicture());
                $mailer->send($message);
                $count += 1;
            } elseif ($purpose == 'tiedotus') {
                $to = 'tiedotus@entropy.fi';
                $message = $this->generateMail($to, $replyto, $subject, $body, $links, $event->getPicture());
                $mailer->send($message);
                $count += 1;
            }
            $email->setSentAt(new \DateTimeImmutable('now'));
            $this->admin->update($email);
            $this->addFlash('sonata_flash_success', sprintf('%s %s info packages sent.', $count, $purpose));
        }
        return new RedirectResponse($this->admin->generateUrl('list', $this->admin->getFilterParameters()));
    }
    private function generateMail($to, $replyto, $subject, $body, $links, $img): TemplatedEmail
    {
        return (new TemplatedEmail())
            ->from(new Address('webmaster@entropy.fi', 'Entropy ry'))
            ->to($to)
            ->replyTo($replyto)
            ->subject($subject)
            ->htmlTemplate('emails/email.html.twig')
            ->context(['body' => $body, 'links' => $links, 'img' => $img]);
    }
}
