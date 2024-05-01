<?php

namespace App\Form;

use App\Entity\PosteOne;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Validator\Constraints\Date;

class PosteOneType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', HiddenType::class, [
                    'data' => 'Poste 1',
            ])
            ->add('start', DateTimeType::class, [
                'date_widget' => 'single_text',
                'time_widget' => 'text',
                'data' => (new \DateTime('today 14:00')),
            ])
            ->add('end', DateTimeType::class, [
                'date_widget' => 'single_text',
                'time_widget' => 'text',
                'data' => (new \DateTime('today 11:00')),
            ])
            
            ->add('background_color', HiddenType::class, [
                'data' => '#FF7F00',
            ])
            ->add('action', HiddenType::class, [
                'mapped' => false,
            ]);
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PosteOne::class,
        ]);
    }
}
