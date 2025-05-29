<?php

namespace App\Repository;

use App\Entity\Conversation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Conversation>
 */
class ConversationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Conversation::class);
    }

    //    /**
    //     * @return Conversation[] Returns an array of Conversation objects
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

    public function findOneByCouple($profileA, $profileB): ?Conversation
    {
        return $this->createQueryBuilder('c')
            ->join('c.participants', 'p')
            ->andWhere('p IN (:profile)')
            ->groupBy('c.id')
            ->having('COUNT(DISTINCT p.id) = 2')
            ->setParameter('profile', [$profileA, $profileB])
            ->getQuery()
            ->getOneOrNullResult()
            ;
    }
}
