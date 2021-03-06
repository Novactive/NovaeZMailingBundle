<?php

/**
 * NovaeZMailingBundle Bundle.
 *
 * @package   Novactive\Bundle\eZMailingBundle
 *
 * @author    Novactive <s.morel@novactive.com>
 * @copyright 2018 Novactive
 * @license   https://github.com/Novactive/NovaeZMailingBundle/blob/master/LICENSE MIT Licence
 */

declare(strict_types=1);

namespace Novactive\Bundle\eZMailingBundle\Form;

use Doctrine\ORM\EntityManagerInterface;
use Novactive\Bundle\eZMailingBundle\Entity\Campaign;
use Novactive\Bundle\eZMailingBundle\Entity\MailingList;
use Novactive\Bundle\eZMailingBundle\Security\Voter\Campaign as CampaignVoter;
use Novactive\Bundle\eZMailingBundle\Security\Voter\Mailing as MailingVoter;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class RegistrationType extends AbstractType
{
    /**
     * @var AuthorizationCheckerInterface
     */
    private $authorizationChecker;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker,
        EntityManagerInterface $entityManager
    ) {
        $this->authorizationChecker = $authorizationChecker;
        $this->entityManager = $entityManager;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('user', UserType::class);
        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) {
                $form = $event->getForm();
                $allowedMailingList = [];
                $campaignRepository = $this->entityManager->getRepository(Campaign::class);
                // permissions on Campaing can be more complex, then we don't filter in SQL
                foreach ($campaignRepository->findAll() as $campaign) {
                    if ($this->authorizationChecker->isGranted(CampaignVoter::VIEW, $campaign)) {
                        foreach ($campaign->getMailingLists() as $mailingList) {
                            if ($this->authorizationChecker->isGranted(MailingVoter::VIEW, $mailingList)) {
                                $allowedMailingList[] = $mailingList;
                            }
                        }
                    }
                }

                $form
                    ->add(
                        'mailingLists',
                        EntityType::class,
                        [
                            'class' => MailingList::class,
                            'choices' => $allowedMailingList,
                            'expanded' => true,
                            'multiple' => true,
                            'required' => true,
                        ]
                    );
            }
        );
    }
}
