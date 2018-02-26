<?php

namespace Entropy\TunkkiBundle\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Sonata\AdminBundle\Route\RouteCollection;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Sonata\CoreBundle\Form\Type\DateTimePickerType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

class EventAdmin extends AbstractAdmin
{
    protected $ts;
    protected $mm;
    protected $parentAssociationMapping = 'item';
    /**
     * @param DatagridMapper $datagridMapper
     */
    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('description')
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
        if (!$this->isChild()){
            $listMapper->add('item');
        }
        $listMapper
            ->add('description')
            ->add('creator')
            ->add('createdAt')
            ->add('_action', null, array(
                'actions' => array(
                    'show' => array(),
                    'edit' => array(),
     //               'delete' => array(),
                )
            ))
        ;
    }

    /**
     * @param FormMapper $formMapper
     */
    protected function configureFormFields(FormMapper $formMapper)
    {
        if (!$this->isChild()){
            $formMapper
                ->with('Item', ['class'=>'col-md-12'])
				->add('item')
				->end()
            ;
		}
		if ($this->getSubject()->getItem() != NULL ){
			$formMapper
				->with('Status', ['class'=>'col-md-4'])
				->add('item.needsFixing', CheckboxType::class,['required' => false])
				->add('item.forSale', CheckboxType::class,['required' => false])
				->add('item.toSpareParts', CheckboxType::class,['required' => false])
				->end()
				->with('Message', ['class' => 'col-md-8'])
				->add('description',TextareaType::class, array('required' => false))
				->end()
			;
			if (!$this->isChild()){
				$formMapper
					->with('Meta')
					->add('creator', null, array('disabled' => true))
					->add('createdAt',DateTimePickerType::class, array('disabled' => true))
					->add('modifier', null, array('disabled' => true))
					->add('updatedAt',DateTimePickerType::class, array('disabled' => true))
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
            ->add('item')
            ->add('description')
            ->add('creator')
            ->add('createdAt')
            ->add('modifier')
            ->add('updatedAt')
        ;
    }
    public function prePersist($Event)
    {
        $user = $this->ts->getToken()->getUser();
        $Event->setCreator($user);
        $Event->setModifier($user);
    }
    public function postPersist($Event)
	{
        $user = $Event->getCreator();
		$text = $this->getMMtext($Event, $user);
        $this->mm->SendToMattermost($text);
    }
    public function preUpdate($Event)
    {
        $user = $this->ts->getToken()->getUser();
        $Event->setModifier($user);
	}
    public function postUpdate($Event)
	{
        $user = $Event->getModifier();
		$text = $this->getMMtext($Event, $user);
        $this->mm->SendToMattermost($text);
	}
	private function getMMtext($Event, $user)
	{
        $text = 'EVENT: <'.$this->generateUrl('show', ['id'=>$Event->getId()], UrlGeneratorInterface::ABSOLUTE_URL).'|'.
                $Event->getItem()->getName().'> ';
		$fix = $Event->getItem()->getNeedsFixing();
        if($fix == true){
            $text .= '**_BROKEN_** ';
        }
        elseif($fix == false){
            $text .= '**_FIXED_** ';
		}
		if($Event->getDescription()){
			$text .= 'with comment: '.$Event->getDescription();
		}
		$text .= ' by '. $user;
		return $text;
	}
    public function __construct($code, $class, $baseControllerName, $mm=null, $ts=null) 
    { 
        $this->mm = $mm; 
        $this->ts = $ts; 
        parent::__construct($code, $class, $baseControllerName); 
	}
}
