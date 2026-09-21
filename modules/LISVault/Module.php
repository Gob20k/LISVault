<?php
namespace LISVault;

use Omeka\Module\AbstractModule;
use Omeka\Entity\Vocabulary;
use Omeka\Entity\Property;
use Laminas\EventManager\SharedEventManagerInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;

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
            'Omeka\Api\Adapter\ItemAdapter',
            'api.create.post',
            [$this, 'attachItemToSites']
        );
    }

    public function attachItemToSites($event)
    {
        $response = $event->getParam('response');
        if (!$response) {
            return;
        }

        $item = $response->getContent();
        if (!$item) {
            return;
        }

        // At this stage in the lifecycle $item is the raw Doctrine entity,
        // not the API Representation — so getId(), not id().
        $itemId = method_exists($item, 'getId') ? $item->getId() : $item->id();

        $api = $this->getServiceLocator()->get('Omeka\ApiManager');
        $sites = $api->search('sites')->getContent();
        if (!$sites) {
            return;
        }

        $siteRefs = [];
        foreach ($sites as $site) {
            $siteRefs[] = ['o:id' => $site->id()];
        }

        $api->update('items', $itemId, ['o:site' => $siteRefs], [], ['isPartial' => true]);
    }
}