<?php

namespace App\Controller;

use App\Entity\DoorLog;
use App\Entity\User;
use App\Form\OpenDoorType;
use App\Helper\SSH;
use App\Helper\Mattermost;
use App\Helper\ZMQHelper;
use App\Helper\Barcode;
use App\Helper\AppleWallet;
use App\Repository\DoorLogRepository;
use Doctrine\ORM\EntityManagerInterface;
use Picqer\Barcode\BarcodeGeneratorHTML;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
class KerdeController extends AbstractController
{
    #[Route(path: ['en' => '/kerde/door', 'fi' => '/kerde/ovi'], name: 'kerde_door')]
    public function door(
        Request $request,
        FormFactoryInterface $formF,
        Mattermost $mm,
        ZMQHelper $zmq,
        Barcode $barcode,
        EntityManagerInterface $em,
        DoorLogRepository $doorlogrepo
    ): RedirectResponse|Response {
        $user = $this->getUser();
        assert($user instanceof User);
        $member = $user->getMember();

        $DoorLog = new DoorLog();
        $DoorLog->setMember($member);
        $since = new \DateTime('now-1day');
        if ($request->get('since')) {
            //$datestring = strtotime($request->get('since'));
            $since = new \DateTime($request->get('since'));
        }
        $logs = $doorlogrepo->getSince($since);
        $form = $formF->create(OpenDoorType::class, $DoorLog);
        $now = new \DateTime('now');
        $env = $this->getParameter('kernel.debug') ? 'dev' : 'prod';
        $status = $zmq->send($env . ' init: ' . $member->getUsername() . ' ' . $now->getTimestamp());
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $doorlog = $form->getData();
            $em->persist($doorlog);
            $em->flush();
            $status = $zmq->send($env . ' open: ' . $member->getUsername() . ' ' . $now->getTimestamp());
            // $this->addFlash('success', 'profile.door.opened');
            $this->addFlash('success', $status);

            $send = true;
            $text = '**Kerde door opened by ' . $doorlog->getMember();
            if ($doorlog->getMessage()) {
                $text .= ' - ' . $doorlog->getMessage();
            } else {
                foreach ($logs as $log) {
                    if (!$log->getMessage() && ($now->getTimestamp() - $log->getCreatedAt()->getTimeStamp() < 60 * 60 * 4)) {
                        $send = false;
                        break;
                    }
                }
            }
            $text .= '**';
            if ($send) {
                $mm->SendToMattermost($text, 'kerde');
            }

            return $this->redirectToRoute('kerde_door');
        }
        $Mbarcode = $barcode->getBarcode($member);
        return $this->render('kerde/door.html.twig', [
            'form' => $form,
            'logs' => $logs,
            'member' => $member,
            'status' => $status,
            'barcode' => $Mbarcode
        ]);
    }
    #[Route('/kerde/recording/start', name: 'recording_start')]
    public function recordingStart(SSH $ssh): RedirectResponse
    {
        $user = $this->getUser();
        assert($user instanceof User);
        $member = $user->getMember();
        if ($member->getIsActiveMember()) {
            $err = $ssh->sendCommand('start');
            if ($err) {
                $this->addFlash('warning', 'Error: ' . $err);
            } else {
                $this->addFlash('success', 'stream.command.successful');
            }
        }
        return $this->redirectToRoute('kerde_door');
    }
    #[Route('/kerde/recording/stop', name: 'recording_stop')]
    public function recordingStop(SSH $ssh): RedirectResponse
    {
        $user = $this->getUser();
        assert($user instanceof User);
        $member = $user->getMember();
        if ($member->getIsActiveMember()) {
            $err = $ssh->sendCommand('stop');
            if ($err) {
                $this->addFlash('warning', 'Error: ' . $err);
            } else {
                $this->addFlash('success', 'stream.command.successful');
            }
        }
        return $this->redirectToRoute('kerde_door');
    }
    #[Route('/kerde/barcodes', name: 'kerde_barcodes')]
    public function index(Barcode $gen): Response
    {
        $barcodes = [];
        $user = $this->getUser();
        assert($user instanceof User);
        $member = $user->getMember();
        $generator = new BarcodeGeneratorHTML();
        $code = $gen->getBarcode($member);
        $barcodes['Your Code'] = $code[1];
        $barcodes['10€'] = $generator->getBarcode('_10e_', $generator::TYPE_CODE_128, 2, 90);
        $barcodes['20€'] = $generator->getBarcode('_20e_', $generator::TYPE_CODE_128, 2, 90);
        $barcodes['Cancel'] = $generator->getBarcode('_CANCEL_', $generator::TYPE_CODE_128, 2, 90);
        $barcodes['Manual'] = $generator->getBarcode('1812271001', $generator::TYPE_CODE_128, 2, 90);
        // $barcodes['Statistics'] = $generator->getBarcode('0348030005', $generator::TYPE_CODE_128, 2, 90);
        return $this->render('kerde/barcodes.html.twig', [
            'barcodes' => $barcodes
        ]);
    }

    #[Route('/kerde/appleWallet', name: 'appleWallet')]
    public function appleWallet(AppleWallet $appleWallet): Response
    {
        $user = $this->getUser();
        assert($user instanceof User);
        $member = $user->getMember();
        return new Response($appleWallet->getPass($member->getId(), 60.187340, 24.83650, 'Kerhohuone', $member->getName()));
    }
}
