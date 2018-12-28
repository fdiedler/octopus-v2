<?php

namespace App\Form\Type;

use App\Entity\TimePeriod;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @author Benoit Del Basso <bdelbasso@users.noreply.github.com>
 */
class TimePeriodsType extends AbstractType
{

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'default_active_time_period_from_now' => true,
            'entry_type'   => TimePeriodType::class,
            'allow_add' => true,
            'allow_delete' => true,
            'prototype_data' => new TimePeriod(new \DateTime),
        ));
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $preSetDataCallable = function(FormEvent $e) use($options) {
            $p = $e->getData();
            if($options['default_active_time_period_from_now']
                && $p->isEmpty()
            ) {
                $p->add(new TimePeriod(new \DateTime));
            }
            return $p;
        };

        $builder
            // the listener must have a higher priority than the one defined in CollectionType
            ->addEventListener(FormEvents::PRE_SET_DATA, $preSetDataCallable, 100)
        ;
        parent::buildForm($builder, $options);
    }

    public function getParent()
    {
        return CollectionType::class;
    }

}
