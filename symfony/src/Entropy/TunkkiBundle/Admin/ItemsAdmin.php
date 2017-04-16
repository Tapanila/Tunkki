<?php

namespace Entropy\TunkkiBundle\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;

class ItemsAdmin extends AbstractAdmin
{
    /**
     * @param DatagridMapper $datagridMapper
     */
    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
//            ->add('id')
            ->add('name')
            ->add('manufacturer')
            ->add('model')
            ->add('serialnumber')
            ->add('description')
            ->add('placeinstorage')
            ->add('whoCanRent', null, array(), 'choice', array(
                'choices'=>
                    array(
                          'Aktiiveille' => 'Aktiiveille', 'Tuttavajärjestöille ja aktiiveille' => 'Tuttavajärjestöille ja aktiiveille', 
                          'Vain aktiiveille' => 'Vain aktiiveille', 'Ei Vuokrata' => 'Ei Vuokrata'
                         )
                 ))
            ->add('status', null, array(), 'choice', array(
                'choices'=>
                    array(
                          'OK' => 'OK', 'Rikki' => 'Rikki', 'Ei voi korjata' => 'Ei voi korjata', 
                          'Puutteellinen' => 'Puutteellinen', 'Kateissa' => 'Kateissa'
                         )
                 ))
            ->add('rent')
            ->add('rentNotice')
//            ->add('fixingHistory')
            ->add('needsFixing')
            ->add('rentHistory')
            ->add('history')
            ->add('forSale')
            ->add('commission')
            ->add('createdAt')
            ->add('updatedAt')
            ->add('creator')
        ;
    }

    /**
     * @param ListMapper $listMapper
     */
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
//            ->add('id')
            ->addIdentifier('name')
//            ->add('manufacturer')
 //           ->add('model')
 //           ->add('description')
 //           ->add('whoCanRent')
            ->add('status', 'choice', array(
                'multiple' => true,
                'choices'=>
                    array(
                          'OK' => 'OK', 'Rikki' => 'Rikki', 'Ei voi korjata' => 'Ei voi korjata', 
                          'Puutteellinen' => 'Puutteellinen', 'Kateissa' => 'Kateissa'
                         )
                 ))
            ->add('rent', 'currency', array(
                'currency' => 'Eur'
                ))
            //->add('rentNotice')
            ->add('placeinstorage')
            ->add('needsFixing', null, array('editable'=>true))
//            ->add('rentHistory')
//            ->add('history')
//            ->add('forSale', null, array('editable'=>true))
//            ->add('createdAt')
//            ->add('updatedAt')
//            ->add('creator')
            ->add('_action', null, array(
                'actions' => array(
                    'show' => array(),
                    'edit' => array(),
                    'delete' => array(),
                )
            ))
        ;
    }

    /**
     * @param FormMapper $formMapper
     */
    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
//            ->add('id')
        ->tab('General')
            ->with('Item', array('class' => 'col-md-6'))
                ->add('name')
                ->add('manufacturer')
                ->add('model')
                ->add('serialnumber')
                ->add('placeinstorage')
/*                ->add('tags', 'sonata_type_model_autocomplete', array(
                    'property' => 'name',
                    'multiple' => 'true',
                    'required' => false,
                ))*/
                ->add('description', 'textarea', array('required' => false, 'label' => 'Item description'))
                ->add('commission', 'sonata_type_date_picker')
            ->end()
            ->with('Rent Information', array('class' => 'col-md-6'))
                ->add('forSale')
                ->add('whoCanRent', 'choice', array(
                    'choices'=>
                        array(
                              'Aktiiveille' => 'Aktiiveille', 'Tuttavajärjestöille ja aktiiveille' => 'Tuttavajärjestöille ja aktiiveille', 
                              'Vain aktiiveille' => 'Vain aktiiveille', 'Ei Vuokrata' => 'Ei Vuokrata'
                             )
                     ))
                ->add('status', 'choice', array(
                    'multiple' => true,
                    'choices'=>
                        array(
                              'OK' => 'OK', 'Rikki' => 'Rikki', 'Ei voi korjata' => 'Ei voi korjata', 
                              'Puutteellinen' => 'Puutteellinen', 'Kateissa' => 'Kateissa'
                             )
                     ))
                ->add('rent')
                ->add('rentNotice', 'textarea', array('required' => false))
            ->end()
        ->end()
        ->tab('Events')
        ->with('Events')
            ->add('needsFixing')
            ->add('fixingHistory', 'sonata_type_collection', array(
                    'label' => null, 'btn_add'=>'Add new event', 
                    'by_reference'=>false,
                    'cascade_validation' => true, 
                    'type_options' => array('delete' => false),
                    'required' => false),
                    array('edit'=>'inline', 'inline'=>'table'))
            ->add('rentHistory')
            ->add('history')
        ->end() 
        ->end()
        ->tab('Files')
        ->with('Files')
            ->add('files', 'sonata_type_collection', array(
                    'label' => null, 'btn_add'=>'Add new file', 
                    'by_reference'=>false,
                    'cascade_validation' => true, 
                    'type_options' => array('delete' => true),
                    'required' => false),
                    array('edit'=>'inline', 'inline'=>'table'))
        ->end() 
        ->end();
        $subject = $this->getSubject();
        if($subject){
            if($subject->getCreatedAt()){
                $formMapper
                    ->tab('Meta')
                    ->with('Meta')
                        ->add('createdAt', 'sonata_type_datetime_picker', array('disabled' => true))
                        ->add('updatedAt', 'sonata_type_datetime_picker', array('disabled' => true))
                        ->add('creator', null, array('disabled' => true))
                    ->end()
                    ;
            }
        }
    }

    /**
     * @param ShowMapper $showMapper
     */
    protected function configureShowFields(ShowMapper $showMapper)
    {
        $showMapper
//            ->add('id')
            ->add('name')
            ->add('manufacturer')
            ->add('model')
            ->add('serialnumber')
            ->add('description')
            ->add('commission', 'sonata_type_date_picker')
            ->add('whoCanRent')
            ->add('status', 'choice', array(
                'multiple' => true,
                'choices'=>
                    array(
                          'OK' => 'OK', 'Rikki' => 'Rikki', 'Ei voi korjata' => 'Ei voi korjata', 
                          'Puutteellinen' => 'Puutteellinen', 'Kateissa' => 'Kateissa'
                         )
                 ))
            ->add('rent')
            ->add('rentNotice')
            ->add('needsFixing')
            ->add('rentHistory')
            ->add('history')
            ->add('forSale')
            ->add('createdAt')
            ->add('updatedAt')
            ->add('creator')
        ;
    }
    public function prePersist($Items)
    {
        $user = $this->getConfigurationPool()->getContainer()->get('security.token_storage')->getToken()->getUser();
        $username = $user->getFirstname()." ".$user->getLastname();
        $Items->setCreator($username);
        foreach ($Items->getfixingHistory() as $history) {
            if($history->getCreator()==''){ 
                $history->setCreator($username);
            }
        } 
    }
    public function preUpdate($Items)
    {
        $user = $this->getConfigurationPool()->getContainer()->get('security.token_storage')->getToken()->getUser();
        $username = $user->getFirstname()." ".$user->getLastname();
        $Items->setCreator($username);
        foreach ($Items->getfixingHistory() as $history) {
            if($history->getCreator()==''){ 
                $history->setCreator($username);
            }
        } 
    }
}
