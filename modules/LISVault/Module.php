<?php
namespace LISVault;

use Omeka\Module\AbstractModule;
use Omeka\Entity\Vocabulary;
use Omeka\Entity\Property;
use Laminas\EventManager\SharedEventManagerInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Laminas\Mvc\MvcEvent;

class Module extends AbstractModule
{
    public function getConfig()
    {
        return [
            'view_manager' => [
                'template_path_stack' => [
                    __DIR__ . '/view',
                ],
            ],
        ];
    }

    public function install(ServiceLocatorInterface $serviceLocator)
    {
        $entityManager = $serviceLocator->get('Omeka\EntityManager');

        $vocabulary = new Vocabulary;
        $vocabulary->setNamespaceUri('http://lisvault.local/vocab#');
        $vocabulary->setPrefix('lisvault');
        $vocabulary->setLabel('LISVault');
        $vocabulary->setComment('Custom metadata fields for the LISVault digital library.');
        $entityManager->persist($vocabulary);

        $studyYear = new Property;
        $studyYear->setVocabulary($vocabulary);
        $studyYear->setLocalName('studyYear');
        $studyYear->setLabel('Year');
        $studyYear->setComment('Which year of study this resource belongs to.');
        $entityManager->persist($studyYear);

        $resourceType = new Property;
        $resourceType->setVocabulary($vocabulary);
        $resourceType->setLocalName('resourceType');
        $resourceType->setLabel('Resource Type');
        $resourceType->setComment('The kind of resource: Lecture Notes, Study Guide, Article, Past Paper, etc.');
        $entityManager->persist($resourceType);

        $entityManager->flush();
    }

    public function uninstall(ServiceLocatorInterface $serviceLocator)
    {
        $entityManager = $serviceLocator->get('Omeka\EntityManager');
        $vocabulary = $entityManager->getRepository(Vocabulary::class)
            ->findOneBy(['prefix' => 'lisvault']);

        if ($vocabulary) {
            $properties = $entityManager->getRepository(Property::class)
                ->findBy(['vocabulary' => $vocabulary]);
            foreach ($properties as $property) {
                $entityManager->remove($property);
            }
            $entityManager->remove($vocabulary);
            $entityManager->flush();
        }
    }

    public function attachListeners(SharedEventManagerInterface $sharedEventManager)
    {
        $sharedEventManager->attach(
            'Omeka\Controller\Admin\Item',
            'view.add.form.after',
            [$this, 'addModuleField']
        );
        $sharedEventManager->attach(
            'Omeka\Controller\Admin\Item',
            'view.edit.form.after',
            [$this, 'addModuleField']
        );
        $sharedEventManager->attach(
            'Omeka\Controller\Admin\Item',
            'view.add.before',
            [$this, 'enqueueAssets']
        );
        $sharedEventManager->attach(
            'Omeka\Controller\Admin\Item',
            'view.edit.before',
            [$this, 'enqueueAssets']
        );

        // Automatically check every new item into every existing site —
        // this is exactly what checking the "Sites" tab by hand does,
        // just fired the moment the item is created.
        $sharedEventManager->attach(
            'Omeka\Api\Adapter\ItemAdapter',
            'api.create.post',
            [$this, 'attachItemToSites']
        );
    }

    public function enqueueAssets(MvcEvent $event)
    {
        $view = $event->getTarget();
        $view->headLink()->appendStylesheet($view->assetUrl('css/lisvault-admin.css', 'LISVault'));
        $view->headScript()->appendFile($view->assetUrl('js/lisvault-admin.js', 'LISVault'));
    }

    public function addModuleField($event)
    {
        $view = $event->getTarget();
        $itemSets = $this->getServiceLocator()
            ->get('Omeka\ApiManager')
            ->search('item_sets', ['sort_by' => 'title'])
            ->getContent();

        echo $view->partial('lisvault/admin/module-select', [
            'itemSets' => $itemSets,
        ]);
    }

    public function attachItemToSites($event)
    {
        $response = $event->getParam('response');
        if (!$response) {
            return;
        }
        $item = $response->getContent();

        $api = $this->getServiceLocator()->get('Omeka\ApiManager');
        $sites = $api->search('sites')->getContent();
        if (!$sites) {
            return;
        }

        $siteRefs = [];
        foreach ($sites as $site) {
            $siteRefs[] = ['o:id' => $site->id()];
        }

        $api->update('items', $item->id(), ['o:site' => $siteRefs], [], ['isPartial' => true]);
    }
}