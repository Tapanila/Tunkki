<?php

declare(strict_types=1);

namespace App\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use FOS\CKEditorBundle\Form\Type\CKEditorType;
use Sonata\Form\Type\DateTimePickerType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

final class ContractAdmin extends AbstractAdmin
{
    protected function configureDatagridFilters(DatagridMapper $datagridMapper): void
    {
        $datagridMapper
            ->add('purpose')
            ->add('validFrom')
            ->add('updatedAt')
            ->add('createdAt');
    }

    protected function configureListFields(ListMapper $listMapper): void
    {
        $listMapper
            ->add('purpose')
            ->add('validFrom')
            ->add('ContentFi', 'html')
            ->add('ContentEn', 'html')
            ->add('updatedAt')
            ->add('createdAt')
            ->add(ListMapper::NAME_ACTIONS, null, [
                'actions' => [
                    'show' => [],
                    'edit' => [],
                    'delete' => [],
                ],
            ]);
    }

    protected function configureFormFields(FormMapper $formMapper): void
    {
        $formMapper
            ->add('purpose', ChoiceType::class, ['choices' => $this->getPurposeChoices()])
            ->add('validFrom', DateTimePickerType::class, [
                'required' => false,
                'format' => 'd.M.y H:mm',
                'datepicker_options' => [
                    'display' => [
                        'sideBySide' => true,
                        'components' => [
                            'seconds' => false,
                        ]
                    ]
                ],
            ])
            ->add('ContentFi', CKEditorType::class, [
                'config' => ['full']
            ])
            ->add('ContentEn', CKEditorType::class, [
                'config' => ['full']
            ]);
    }

    protected function configureShowFields(ShowMapper $showMapper): void
    {
        $showMapper
            ->add('purpose')
            ->add('validFrom')
            ->add('ContentFi')
            ->add('ContentEn')
            ->add('createdAt')
            ->add('updatedAt');
    }
    private function getPurposeChoices(): array
    {
        return ['Rent Contract' => 'rent'];
    }
}
