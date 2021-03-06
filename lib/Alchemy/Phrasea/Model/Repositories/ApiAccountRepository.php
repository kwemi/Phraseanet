<?php

namespace Alchemy\Phrasea\Model\Repositories;

use Alchemy\Phrasea\Model\Entities\ApiApplication;
use Alchemy\Phrasea\Model\Entities\User;
use Doctrine\ORM\EntityRepository;

/**
 * ApiAccountRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class ApiAccountRepository extends EntityRepository
{
    public function findByUserAndApplication(User $user, ApiApplication $application)
    {
        $qb = $this->createQueryBuilder('acc');
        $qb->where($qb->expr()->eq('acc.user', ':user'));
        $qb->andWhere($qb->expr()->eq('acc.application', ':app'));
        $qb->setParameter(':user', $user);
        $qb->setParameter(':app', $application);

        return $qb->getQuery()->getOneOrNullResult();
    }

    public function findByUser(User $user)
    {
        $qb = $this->createQueryBuilder('acc');
        $qb->where($qb->expr()->eq('acc.user', ':user'));
        $qb->setParameter(':user', $user);

        return $qb->getQuery()->getResult();
    }
}
