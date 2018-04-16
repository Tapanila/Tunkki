<?php

namespace Entropy\TunkkiBundle\Entity;

/**
 * BookingRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class BookingRepository extends \Doctrine\ORM\EntityRepository
{
	public function findBookingsAtTheSameTime($id, $startAt, $endAt)
	{
		$queryBuilder = $this->createQueryBuilder('b')
					   ->andWhere('b.retrieval BETWEEN :startAt and :endAt')
					   ->orWhere('b.returning BETWEEN :startAt and :endAt')
					   ->andWhere('b.itemsReturned = false')
					   ->andWhere('b.id != :id')
					   ->setParameter('startAt', $startAt)
					   ->setParameter('endAt', $endAt)
					   ->setParameter('id', $id)
					   ->orderBy('b.name', 'ASC');
		return $queryBuilder->getQuery()->getResult();
	}
	public function getBookingData($id, $hash = null, $renter = null)
	{
		$queryBuilder = $this->createQueryBuilder('b')
					   ->andWhere('b.renterHash = :hash')
					   ->andWhere('b.id = :id')
					   ->andWhere('b.renter = :renter')
					   ->setParameter('hash', $hash)
					   ->setParameter('renter', $renter)
					   ->setParameter('id', $id);
        $object = $queryBuilder->getQuery()->getOneOrNullResult();
		$items = []; 
		$packages = []; 
		$accessories = []; 
		$rent['items'] = 0; 
		$compensation['items'] = 0; 
		$rent['packages'] = 0; 
		$compensation['packages'] = 0; 
		$rent['accessories'] = 0; 
		$compensation['accessories'] = 0; 
		foreach ($object->getItems() as $item){ 
			$items[]=$item; 
			$rent['items']+=$item->getRent(); 
			$compensation['items']+=$item->getCompensationPrice(); 
		} 
		foreach ($object->getPackages() as $item){ 
			$packages[]=$item;
		} 
		foreach ($object->getAccessories() as $item){ 
			$accessories[]=$item; 
			$compensation['accessories']+=$item->getName()->getCompensationPrice()*$item->getCount(); 
		} 
		$rent['total'] = $rent['items'] + $rent['packages']; //+ $rent['accessories']; 
		$rent['actualTotal']=$object->getActualPrice(); 

		$data['name']=$object->getName(); 
		$data['date']=$object->getBookingDate()->format('j.n.Y'); 
		$data['items']=$items; 
		$data['packages']=$packages; 
		$data['accessories']=$accessories; 
		$data['rent']=$rent; 
		$data['compensation']=$compensation; 
		return $data;
	}
}
