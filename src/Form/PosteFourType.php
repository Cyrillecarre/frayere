<?php

namespace App\Form;

use App\Entity\PosteFour;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\Email;

class PosteFourType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', HiddenType::class, [
                    'data' => 'Poste 4',
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
            ->add('email', TextType::class, [
                'required' => true,
                'attr' => [
                    'placeholder' => 'exemple@email.com',
                ],
                'constraints' => [
                    new Email([
                        'message' => 'L\'adresse email "{{ value }}" n\'est pas valide.',
                    ]),
                ],
            ])
            ->add('phoneNumber', TextType::class, [
                'required' => true,
                'attr' => [
                    'placeholder' => '0612345678',
                ],
                'constraints' => [
                    new Regex([
                        'pattern' => '/^\d+$/',
                        'message' => 'Le format du numéro doit être 0612345678',
                    ]),
                ],
            ]);
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PosteFour::class,
        ]);
    }
}
