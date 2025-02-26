<?php
namespace App\Repository;

use App\Entity\Service;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Query\Expr;

class ServiceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Service::class);
    }

    public function searchByNameOrDescription(string $query, int $page, int $perPage): array
    {
        $qb = $this->createQueryBuilder('s');

        if (!empty($query)) {
            $qb->where($qb->expr()->like('LOWER(s.name)', ':query'))
               ->orWhere($qb->expr()->like('LOWER(s.description)', ':query'))
               ->setParameter('query', '%' . strtolower($query) . '%');
        }

        $qb->setMaxResults($perPage)
           ->setFirstResult(($page - 1) * $perPage);

        // 🔍 Debug: Log the SQL Query
        error_log("SQL Query: " . $qb->getQuery()->getSQL());
        error_log("Search Parameter: " . '%' . strtolower($query) . '%');

        return $qb->getQuery()->getResult();
    }

    public function countBySearchQuery(string $query): int
    {
        $qb = $this->createQueryBuilder('s')
            ->select('COUNT(s.id)');

        if (!empty($query)) {
            $qb->where($qb->expr()->like('LOWER(s.name)', ':query'))
               ->orWhere($qb->expr()->like('LOWER(s.description)', ':query'))
               ->setParameter('query', '%' . strtolower($query) . '%');
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }
}