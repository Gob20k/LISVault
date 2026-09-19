<?php
declare(strict_types=1);

use Laminas\Mvc\Application;
use Omeka\Entity\User;

require dirname(__DIR__, 3) . '/bootstrap.php';
$application = Application::init(require OMEKA_PATH . '/application/config/application.config.php');
$services = $application->getServiceManager();
$entityManager = $services->get('Omeka\\EntityManager');
$owner = $entityManager->getRepository(User::class)->findOneBy([]);
if (!$owner) {
    throw new RuntimeException('An Omeka administrator is required before LISVault can be set up.');
}
$services->get('Omeka\\AuthenticationService')->getStorage()->write($owner);
$api = $services->get('Omeka\\ApiManager');

$student = $entityManager->getRepository(User::class)->findOneBy(['email' => 'student@lisvault.local']);
if (!$student) {
    $student = new User;
    $student->setName('LISVault Student');
    $student->setEmail('student@lisvault.local');
    $student->setPassword('LISVaultStudent2026!');
    $student->setRole('researcher');
    $student->setIsActive(true);
    $student->setCreated(new DateTime());
    $entityManager->persist($student);
    $entityManager->flush();
}

$siteResult = $api->search('sites', ['slug' => 'lisvault', 'limit' => 1])->getContent();
if (!$siteResult) {
    $api->create('sites', [
        'o:slug' => 'lisvault', 'o:title' => 'LISVault', 'o:theme' => 'lisvault',
        'o:summary' => 'Digital learning resources for Library and Information Studies students.',
        'o:is_public' => true,
    ]);
}

$templateResult = $api->search('resource_templates', ['label' => 'LISVault Resource', 'limit' => 1])->getContent();
$templateData = [
        'o:label' => 'LISVault Resource', 'o:resource_type' => 'o:Item',
        'o:resource_template_property' => [
            ['o:property' => ['o:id' => 1], 'o:is_required' => true],
            ['o:property' => ['o:id' => 2]], ['o:property' => ['o:id' => 4]],
            ['o:property' => ['o:id' => 7]], ['o:property' => ['o:id' => 14]],
            ['o:property' => ['o:id' => 33]],
            ['o:property' => ['o:id' => 8]], ['o:property' => ['o:id' => 12]],
            ['o:property' => ['o:id' => 20]], ['o:property' => ['o:id' => 15]],
        ],
    ];
if (!$templateResult) {
    $api->create('resource_templates', $templateData);
} else {
    $api->update('resource_templates', $templateResult[0]->id(), $templateData);
}

$modules = [
    'First Year' => ['Library and Information Practice 1', 'Organisation and Representation of Information 1A', 'Organisation and Representation of Information 1B', 'Communication in Zulu', 'South African Sign Language'],
    'Second Year' => ['Library and Information Practice II', 'Library and Information Professional Practice 1A', 'Organisation and Representation of Information IIA', 'Library Marketing and Promotion', 'Library and Information Professional Practice 1B', 'Organisation and Representation of Information IIB'],
    'Third Year' => ['Library and Information Practice IIIA', 'Organisation and Representation of Information IIIA', 'Library and Information Professional Practice IIA', 'Library and Information Practice IIIB', 'Library and Information Professional Practice IIB', 'Organisation and Representation of Information IIIB'],
];
foreach ($modules as $year => $names) foreach ($names as $name) {
    if (!$api->search('item_sets', ['search' => $name, 'limit' => 1])->getContent()) {
        $api->create('item_sets', ['o:is_public' => true, 'dcterms:title' => [['type' => 'literal', 'property_id' => 1, '@value' => $name]], 'dcterms:description' => [['type' => 'literal', 'property_id' => 4, '@value' => $year . ' module']]]);
    }
}
echo "LISVault setup complete. Site: /omeka/s/lisvault/\nStudent: student@lisvault.local\n";
