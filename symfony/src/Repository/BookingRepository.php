<?php

namespace App\Repository;

use App\Entity\Booking;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

class BookingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Booking::class);
    }
    public function findBookingsAtTheSameTime($id, $startAt, $endAt)
    {
        $queryBuilder = $this->createQueryBuilder('b')
            ->andWhere('b.retrieval BETWEEN :startAt and :endAt')
            ->orWhere('b.returning BETWEEN :startAt and :endAt')
            ->andWhere('b.itemsReturned = false')
            ->andWhere('b.cancelled = false')
            ->andWhere('b.id != :id')
            ->setParameter('startAt', $startAt)
            ->setParameter('endAt', $endAt)
            ->setParameter('id', $id)
            ->orderBy('b.name', 'ASC');
        return $queryBuilder->getQuery()->getResult();
    }
    public function getBookingData($id, $hash = null, $renter = null)
    {
        $rent = [];
        $compensation = [];
        $data = [];
        $queryBuilder = $this->createQueryBuilder('b')
            ->andWhere('b.renterHash = :hash')
            ->andWhere('b.id = :id')
            //->andWhere('b.renter = :renter')
            ->setParameter('hash', $hash)
            //->setParameter('renter', $renter)
            ->setParameter('id', $id);
        $object = $queryBuilder->getQuery()->getOneOrNullResult();
        if (is_object($object)) {
            $items = [];
            $packages = [];
            $accessories = [];
            $rent['items'] = 0;
            $compensation['items'] = 0;
            $rent['packages'] = 0;
            $compensation['packages'] = 0;
            $rent['accessories'] = 0;
            $compensation['accessories'] = 0;
            foreach ($object->getItems() as $item) {
                $items[] = $item;
                $rent['items'] += $item->getRent();
                $compensation['items'] += $item->getCompensationPrice();
            }
            foreach ($object->getPackages() as $item) {
                $packages[] = $item;
                $rent['packages'] += $item->getRent();
                $compensation['packages'] += $item->getCompensationPrice();
            }
            foreach ($object->getAccessories() as $item) {
                $accessories[] = $item;
                if (is_int($item->getCount())) {
                    $compensation['accessories'] += $item->getName()->getCompensationPrice() * $item->getCount();
                }
            }
            $rent['total'] = $rent['items'] + $rent['packages']; //+ $rent['accessories'];

            $data['actualTotal'] = $object->getActualPrice();
            $rent['actualTotal'] = $object->getActualPrice();
            $rent['accessories'] = $object->getAccessoryPrice();
            $data['name'] = $object->getName();
            $data['date'] = $object->getBookingDate()->format('j.n.Y');
            $data['items'] = $items;
            $data['packages'] = $packages;
            $data['accessories'] = $accessories;
            $data['rent'] = $rent;
            $data['compensation'] = $compensation;
            $data['renterSignature'] = $object->getRenterSignature();
            return [$data, $object];
        } else {
            return 'error';
        }
    }
    public function countHandled()
    {
        $qb = $this->createQueryBuilder('b');
        $qb->select($qb->expr()->count('b'))
            ->where('b.cancelled = :is')
            ->setParameter('is', false);
        return $qb->getQuery()->getSingleScalarResult();
    }
}
