<?php

namespace Entropy\TunkkiBundle\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use Sonata\CoreBundle\Validator\ErrorElement;
use Sonata\AdminBundle\Route\RouteCollection;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Sonata\CoreBundle\Form\Type\DateTimePickerType;
use Sonata\CoreBundle\Form\Type\DateRangePickerType;
use Sonata\CoreBundle\Form\Type\DateTimeRangePickerType;
use Sonata\CoreBundle\Form\Type\DatePickerType;
use Sonata\CoreBundle\Form\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Sonata\AdminBundle\Form\Type\ModelType;
use Sonata\AdminBundle\Form\Type\ModelListType;
//use Entropy\TunkkiBundle\Form\Type\ItemsType;
use Entropy\TunkkiBundle\Entity\Item;


class BookingAdmin extends AbstractAdmin
{
    protected $mm; // Mattermost helper
    protected $ts; // Token Storage

	protected $datagridValues = array(
        '_page' => 1,
        '_sort_order' => 'DESC',
        '_sort_by' => 'createdAt',
    );

    /**
     * @param DatagridMapper $datagridMapper
     */
    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('name')
            ->add('items')
            ->add('packages')
            ->add('renter')
            ->add('bookingDate', 'doctrine_orm_date_range',['field_type'=>DateRangePickerType::class])
            ->add('retrieval', 'doctrine_orm_datetime_range',['field_type'=>DateTimeRangePickerType::class])
            ->add('returning', 'doctrine_orm_datetime_range',['field_type'=>DateTimeRangePickerType::class])
            ->add('givenAwayBy')
            ->add('receivedBy')
        ;
    }

    /**
     * @param ListMapper $listMapper
     */
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->addIdentifier('referenceNumber')
            ->addIdentifier('name')
            ->add('renter')
            ->add('bookingDate')
            ->add('retrieval')
            ->add('returning')
            ->add('packages')
            ->add('items')
            ->add('returned', null, array('editable' => true))
            ->add('paid', null, array('editable' => true))
            ->add('_action', null, array(
                'actions' => array(
                    'stuffList' => array(
                        'template' => 'EntropyTunkkiBundle:CRUD:list__action_stuff.html.twig'
                    ),
            //        'show' => array(),
                    'edit' => array(),
                    'delete' => array(),
                ),
            ))
        ;
    }

    /**
     * @param FormMapper $formMapper
     */
    protected function configureFormFields(FormMapper $formMapper)
    {
        $em = $this->modelManager->getEntityManager('Entropy\TunkkiBundle\Entity\Item');

        $subject = $this->getSubject();
        if (!empty($subject->getName())) {
            $forWho = $subject->getRentingPrivileges();
            $retrieval = $subject->getRetrieval();
            $returning = $subject->getReturning();
        }

        $items = $em->createQueryBuilder('c')
                ->select('c')
                ->from('EntropyTunkkiBundle:Item', 'c')
                ->where('c.needsFixing = false')
                ->andwhere('c.toSpareParts = false')
                ->orderBy('c.name', 'ASC')
          //      ->andwhere('c.Retrieval < :retrieval')
          //      ->andwhere('c.Returning > :returning')
          //      ->setParameter('retrieval', $retrieval)
          //      ->setParameter('returning', $returning)
                ;
        $formMapper
            ->tab('General')
            ->with('Booking', array('class' => 'col-md-6'))
                ->add('name')
                ->add('bookingDate', DatePickerType::class, [])
                ->add('retrieval', DateTimePickerType::class, [
                        'dp_side_by_side' => true, 
                        'dp_use_seconds' => false,
                        'with_seconds' => false, 
                        ])
                ->add('givenAwayBy', ModelListType::class, array('btn_add' => false, 'btn_delete' => 'unassign'))
                ->add('returning', DateTimePickerType::class, ['dp_side_by_side' => true, 'dp_use_seconds' => false])
                ->add('receivedBy', ModelListType::class, array('required' => false, 'btn_add' => false, 'btn_delete' => 'unassign'))
                ->add('returned')
            ->end()
            ->with('Who is Renting?', array('class' => 'col-md-6'))
                ->add('renter', ModelListType::class, array('btn_delete' => 'unassign'))
                ->add('rentingPrivileges', null, array('help' => 'Only items that are in this group are shown'))
            ->end()
            ->end();

        $subject = $this->getSubject();
        if (!empty($subject->getName())) {
            $formMapper 
                ->tab('Rentals')
                ->with('The Stuff')
                    ->add('items', ModelType::class, array(
                        'query' => $items, 
                        'multiple' => true, 
                //        'class' => Item::class,
						'template' => false,
                        'by_reference' => false,
                        'btn_add' => false,
                    ))
                    ->add('packages', null, array( //'sonata_type_model', array(
 //                       'query' => $pakages, 
                        'multiple' => true, 
                        'expanded' => true, 
                        'by_reference' => false,
                       // 'btn_add' => false
                    ))
                    ->add('accessories', CollectionType::class, array('required' => false, 'by_reference' => false),
                        array('edit' => 'inline', 'inline' => 'table')
                    )
                    ->add('rentInformation', TextareaType::class, array('disabled' => true))
                ->end()
                ->end()
                ->tab('Payment')
                ->with('Payment Information')
                    ->add('referenceNumber', null, array('disabled' => true))
                    ->add('calculatedTotalPrice', TextType::class, array('disabled' => true))
                    ->add('numberOfRentDays', null, array('help' => 'How many days are actually billed', 'disabled' => false, 'required' => true))
                    ->add('actualPrice', null, array('disabled' => false, 'required' => false))
                ->end()
                ->with('Events', array('class' => 'col-md-12'))
                    ->add('billableEvents', CollectionType::class, array('required' => false, 'by_reference' => false),
                        array('edit' => 'inline', 'inline' => 'table')
                    )
                    ->add('paid')
                    ->add('paid_date', DateTimePickerType::class, array('disabled' => false, 'required' => false))
                ->end()
                ->end()
                ->tab('Meta')
                    ->add('createdAt', DateTimePickerType::class, array('disabled' => true))
                    ->add('creator', null, array('disabled' => true))
                    ->add('modifiedAt', DateTimePickerType::class, array('disabled' => true))
                    ->add('modifier', null, array('disabled' => true))
                ->end()
            ;
        }
    }

    /**
     * @param ShowMapper $showMapper
     */
    protected function configureShowFields(ShowMapper $showMapper)
    {
        $showMapper
            ->add('name')
            ->add('bookingDate')
            ->add('retrieval')
            ->add('returning')
            ->add('renter')
            ->add('items')
            ->add('packages')
            ->add('creator')
            ->add('referenceNumber')
            ->add('actualPrice')
            ->add('returned')
            ->add('billableEvents')
            ->add('paid')
            ->add('paid_date')
            ->add('modifier')
            ->add('modifiedAt')
        ;
    }


    protected function calculateReferenceNumber($booking)
    {
        $ki = 0;
        $summa = 0;
        $kertoimet = [7, 3, 1];
        $viite = '303'.($booking->getId()+1220);

        for ($i = strlen($viite); $i > 0; $i--) {
            $summa += substr($viite, $i - 1, 1) * $kertoimet[$ki++ % 3];
        }
        return $viite.''.(10 - ($summa % 10)) % 10;
    }
    public function prePersist($booking)
    {
        $user = $this->getConfigurationPool()->getContainer()->get('security.token_storage')->getToken()->getUser();
        $booking->setCreator($user);
        $booking->setActualPrice($booking->getCalculatedTotalPrice()*0.9);
    }
    public function postPersist($booking)
    {
        $booking->setReferenceNumber($this->calculateReferenceNumber($booking));
        $user = $this->ts->getToken()->getUser();
        $username = $user->getFirstname()." ".$user->getLastname();
		$text = '#### BOOKING: <'.$this->generateUrl('edit', ['id'=> $booking->getId()],
			UrlGeneratorInterface::ABSOLUTE_URL).'|'.$booking->getName().'> on '.
			$booking->getBookingDate()->format('d.m.Y').' created by '.$username;
        $this->mm->SendToMattermost($text);
    }
    public function preUpdate($booking)
    {
        $user = $this->getConfigurationPool()->getContainer()->get('security.token_storage')->getToken()->getUser();
        $booking->setModifier($user);
    }

    public function getFormTheme()
    {
        return array_merge(
            parent::getFormTheme(),
            array('EntropyTunkkiBundle:BookingAdmin:admin.html.twig')
        );
    }
    public function validate(ErrorElement $errorElement, $object)
    {
        $errorElement
            ->with('retrieval')
                ->assertNotNull(array())
            ->end()
            ->with('returning')
                ->assertNotNull(array())
            ->end()
            ->with('bookingDate')
                ->assertNotNull(array())
            ->end()
            ->with('givenAwayBy')
                ->assertNotNull(array())
            ->end()
            ->with('rentingPrivileges')
                ->assertNotNull(array())
            ->end()
        ;
        if($object->getRetrieval() > $object->getReturning()){
            $errorElement->with('retrieval')->addViolation('Must be before the returning')->end();
            $errorElement->with('returning')->addViolation('Must be after the retrieval')->end();
        }
        if(($object->getReturned() == true) and ($object->getReceivedBy() == null)){
            $errorElement->with('receivedBy')->addViolation('Who checked the rentals back to storage?')->end();
        }
    } 
    protected function configureRoutes(RouteCollection $collection)
    {
        $collection->add('stuffList', $this->getRouterIdParameter().'/stufflist');
    }
    public function __construct($code, $class, $baseControllerName, $mm=null, $ts=null)
    {
        $this->mm = $mm;
        $this->ts = $ts;
        parent::__construct($code, $class, $baseControllerName);
    }
}
