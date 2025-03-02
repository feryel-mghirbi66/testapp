<?php

namespace App\Repository;

use App\Entity\Consultation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Consultation>
 */
class ConsultationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Consultation::class);
    }

//    /**
//     * @return Consultation[] Returns an array of Consultation objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('c.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Consultation
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
public function countConsultationsByService(): array
    {
        return $this->createQueryBuilder('c')
            ->select('s.name as service, COUNT(c.id) as total')
            ->join('c.service', 's')
            ->groupBy('s.id')
            ->getQuery()
            ->getResult();
    }


    public function findConsultationsByDate()
    {
        return $this->createQueryBuilder('c')
            ->orderBy('c.date', 'ASC') // ASC pour tri croissant, DESC pour tri décroissant
            ->getQuery()
            ->getResult();
    }



}
