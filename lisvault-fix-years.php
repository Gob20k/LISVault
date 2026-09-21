<?php
// ONE-TIME script. Delete this file immediately after running it once —
// it has no login check, so leaving it in place is a real security risk.
require 'bootstrap.php';

$application = \Laminas\Mvc\Application::init(require 'application/config/application.config.php');
$entityManager = $application->getServiceManager()->get('Omeka\EntityManager');

$vocabulary = $entityManager->getRepository(\Omeka\Entity\Vocabulary::class)
    ->findOneBy(['prefix' => 'lisvault']);
if (!$vocabulary) {
    die('LISVault vocabulary not found — is the module installed?');
}

$yearProperty = $entityManager->getRepository(\Omeka\Entity\Property::class)
    ->findOneBy(['vocabulary' => $vocabulary, 'localName' => 'studyYear']);
if (!$yearProperty) {
    die('lisvault:studyYear property not found.');
}

$yearMap = [
    'Library and Information Practice 1' => 'First Year',
    'Organisation and Representation of Information 1A' => 'First Year',
    'Organisation and Representation of Information 1B' => 'First Year',
    'Communication in Zulu' => 'First Year',
    'South African Sign Language' => 'First Year',

    'Library and Information Practice II' => 'Second Year',
    'Library and Information Professional Practice 1A' => 'Second Year',
    'Organisation and Representation of Information IIA' => 'Second Year',
    'Library Marketing and Promotion' => 'Second Year',
    'Library and Information Professional Practice 1B' => 'Second Year',
    'Organisation and Representation of Information IIB' => 'Second Year',

    'Library and Information Practice IIIA' => 'Third Year',
    'Organisation and Representation of Information IIIA' => 'Third Year',
    'Library and Information Professional Practice IIA' => 'Third Year',
    'Library and Information Practice IIIB' => 'Third Year',
    'Library and Information Professional Practice IIB' => 'Third Year',
    'Organisation and Representation of Information IIIB' => 'Third Year',
];

$itemSets = $entityManager->getRepository(\Omeka\Entity\ItemSet::class)->findAll();

echo '<pre>';
$updated = 0;
foreach ($itemSets as $itemSet) {
    $title = (string) $itemSet->getTitle();
    if (!isset($yearMap[$title])) {
        echo "SKIPPED (no match): {$title}\n";
        continue;
    }
    $year = $yearMap[$title];

    $existingValue = null;
    foreach ($itemSet->getValues() as $value) {
        if ($value->getProperty() === $yearProperty) {
            $existingValue = $value;
            break;
        }
    }

    if ($existingValue) {
        $existingValue->setValue($year);
    } else {
        $newValue = new \Omeka\Entity\Value;
        $newValue->setResource($itemSet);
        $newValue->setProperty($yearProperty);
        $newValue->setType('literal');
        $newValue->setValue($year);
        $newValue->setIsPublic(true);
        $entityManager->persist($newValue);
        $itemSet->getValues()->add($newValue);
    }

    echo "SET: {$title} -> {$year}\n";
    $updated++;
}

$entityManager->flush();
echo "\n{$updated} modules updated.";
echo '</pre><p><strong>Done — delete this file now.</strong></p>';