<?php

namespace App\Repository;

use App\Entity\Player;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Player>
 *
 * @method Player|null find($id, $lockMode = null, $lockVersion = null)
 * @method Player|null findOneBy(array $criteria, array $orderBy = null)
 * @method Player[]    findAll()
 * @method Player[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PlayerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Player::class);
    }

    public function save(Player $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Player $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

   /**
    *
    * @param string $name
    * @param string $location
    *
    * @return ?Player Returns one Player object or null
    */
   public function findOnePlayerByNameAndOrLocation(string | null $name, string | null $location): ?Player
   {
       $qb = $this->createQueryBuilder('p')
            ->select('p, g')
            ->leftJoin('p.games', 'g');

        if($name) {
            $qb->andWhere('p.name LIKE :name')
                ->setParameter('name', '%'.$name.'%');
        } elseif($location) {
            $qb->andWhere('p.location LIKE :location')
                ->setParameter('location', '%'.$location.'%');
        } elseif($location && $name) {
            $qb->andWhere('p.location LIKE :location')
                ->andWhere('p.name LIKE :name')
                ->setParameters(['location' => '%'.$location.'%', 'name' => '%'.$name.'%']);
        }
        
        $qb->orderBy('p.id', 'DESC');
        
        return $qb->getQuery()->getOneOrNullResult();
   }
}
