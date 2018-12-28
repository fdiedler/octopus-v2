<?php

namespace App\Form\Type;

use App\Entity\TimePeriod;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @author Benoit Del Basso <bdelbasso@users.noreply.github.com>
 */
class TimePeriodType extends AbstractType
{

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => null
        ]);
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->addModelTransformer(new CallbackTransformer(
                function($p) {
                    if(null == $p) return null;
                    return [
                        'start' => $p->getStart(),
                        'end' => $p->getEnd(),
                        'id' => $p->getId(),
                    ];
                },
                function (array $data) {
                    if(empty($data['id'])) {
                        return new TimePeriod($data['start'], $data['end']);
                    } else {
                        $p = $this->em->find(TimePeriod::class, $data['id']);
                        $p->setStart($data['start']);
                        $p->setEnd($data['end']);
                        return $p;
                    }
                }
            ))
            ->add('id', HiddenType::class)
            ->add('start', DateType::class)
            ->add('end', DateType::class, [
                'required' => false,
            ])
        ;
    }

}
