<?php

namespace App\Form;

use App\Entity\City;
use App\Entity\Order;
use App\Repository\CityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

class OrderFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName')
            ->add('lastName')
            ->add('phoneNumber')
            ->add('adress')
            ->add('city', EntityType::class, [
                'class' => City::class,
                'choice_label' => function (City $city) {
                    return $city->getZipCode() . ' - ' . $city->getName();
                },
                'query_builder' => function (CityRepository $er) {
                    return $er->createQueryBuilder('c')
                        ->orderBy('c.zipCode', 'ASC')
                        ->addOrderBy('c.name', 'ASC');
                },
                'label' => 'Ville',
            ])
            ->add('shippingPrice', HiddenType::class, [
                'mapped' => true,
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Order::class,
        ]);
    }
}
