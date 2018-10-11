<?php
declare(strict_types=1);

namespace MVQN\Synchronization;





abstract class Synchronizer implements ISynchronizer
{



    /**
     * @param array $sourceObjects
     * @param array $destinationObjects
     * @param string $mapFile
     * @return SyncMap
     * @throws \Exception
     */
    public static function map(array $sourceObjects, array $destinationObjects): SyncMap
    {
        $syncClass = get_called_class();
        /** @var Synchronizer $syncClass */
        $syncClass = new $syncClass();

        $mapFile = $syncClass->getMapFile();

        // Load the existing JSON map file, if one exists...
        $cached = ($mapFile !== "" && file_exists($mapFile)) ? json_decode(file_get_contents($mapFile), true) : [];
        $map = array_key_exists("map", $cached) ? $map = $cached["map"] : [];

        // Get the source's "key" and "property" property.
        //$sourceKey = $syncClass->getSourceKey();//    "ucrmId"; //$sourceDefinition->getKey();
        //$sourceProperty = "";// "id"; //$sourceDefinition->getProperty();

        // Get the destination's "key" and "property" property.
        //$destinationKey = $syncClass->getDestinationKey();//   "xeroId"; //$destinationDefinition->getKey();
        //$destinationProperty = "GUID"; //$destinationDefinition->getProperty();



        // Duplicate the map to be used as a reverse lookup for determining changes.
        $sourceHandled = $map;

        // Initialize the local source mapping results.
        $sourceChanges = new SyncChanges();

        // Initialize a duplicate validation list.
        $sourceNames = [];

        // Loop through the provided source array first...
        foreach($sourceObjects as $class)
        {
            // Get the currently iterated class.
            $currentClass = get_class($class);

            //if($currentClass !== $sourceDefinition->getClass())
            //    throw new \Exception("Class '$currentClass' must be of ClassMap type '{$sourceDefinition->getClass()}'!");

            $sourceKey = $syncClass->getSourceKey($class);

            if ($sourceKey === "")
                throw new \Exception("Key '$sourceKey' is not in a supported format!");

            $value = $syncClass->getSourceValue($class);

            /*
            // IF a value not was passed for the "property" argument...
            if($sourceProperty === "")
            {
                // THEN include the entire class as the value, but "unbox" to avoid extraneous properties.
                //$value = json_decode(json_encode($class), true);
                $value = $syncClass->getSourceValue($class);
            }
            else
            {
                // OTHERWISE, use the appropriate getter method to get the specified value.
                $getter = "get" . ucfirst($sourceProperty);

                if (!method_exists($class, $getter))
                    throw new \Exception("A getter of '$getter' could not be found on the class '$class'!");

                $value = $class->{$getter}();
            }
            */

            // Generate a "name" for the mapping, using the provided callback.
            $sourceName = $syncClass->getSourceName($class); //$sourceDefinition->generateName($class);

            // IF the map already contains this lookup...
            if(in_array($sourceName, $sourceNames))
            {
                // THEN simply add this name to the list of duplicates and continue!
                $sourceChanges->addDuplicated($sourceName);

                // TODO: Determine how we want to handle this, long term!
                continue;
            }

            // Add the current name to the duplicate verification list.
            $sourceNames[] = $sourceName;



            // IF this is the only occurrence of this "name", but it already exists in the map...
            if (array_key_exists($sourceName, $map))
            {
                // AND, IF the map already contains the mapping "key"...
                if(array_key_exists($sourceKey, $map[$sourceName]))
                {
                    // AND IF the "property" value is NOT the same...
                    if($map[$sourceName][$sourceKey] !== $value)
                    {
                        // THEN update the mapping.
                        $map[$sourceName][$sourceKey] = $value;
                        $sourceChanges->addUpdated($sourceName);
                    }

                    // Mark this mapping as handled!
                    unset($sourceHandled[$sourceName]);
                }
                else
                {
                    // OTHERWISE the map contains the "name", but the "key" does not exist!
                    $map[$sourceName][$sourceKey] = $value;
                    $sourceChanges->addCreated($sourceName);

                    // Mark this one as handled!
                    unset($sourceHandled[$sourceName]);
                }
            }
            else
            {
                // OTHERWISE, this "name" does not exist in the map, but could potentially exist under another name!
                $otherName = "";

                // Check for matching "property" values, in case the mapping was created using a different "name"...
                foreach($map as $name => $oldMappings)
                {
                    if(array_key_exists($sourceKey, $oldMappings) && $oldMappings[$sourceKey] === $value)
                        $otherName = $name;
                }

                // IF a matching "key" => "property" set was found using another "name"...
                if($otherName !== "")
                {
                    // THEN create the new mapping.
                    $map[$sourceName][$sourceKey] = $map[$otherName][$sourceKey];
                    $sourceChanges->addCreated($sourceName);

                    // AND remove the old mapping.
                    unset($map[$otherName][$sourceKey]);
                    $sourceChanges->addDeleted($otherName);

                    // Mark the old one as handled, as the new one would not have been in the list anyways!
                    unset($sourceHandled[$otherName]);
                }
                else
                {
                    // OTHERWISE simply create the mapping!
                    $map[$sourceName] = [];
                    $map[$sourceName][$sourceKey] = $value;
                    $sourceChanges->addCreated($sourceName);

                    // Mark this one as handled!
                    unset($sourceHandled[$sourceName]);
                }
            }
        }

        // Loop through any leftover/unhandled mappings...
        foreach($sourceHandled as $name => $mappings)
        {
            // IF the "key" exists in this mapping...
            if(array_key_exists($sourceKey, $mappings))
            {
                // THEN remove the "key" and add this to the list of "deleted" mappings!
                unset($map[$name][$sourceKey]);
                $sourceChanges->addDeleted($name);
            }

            // Mark this one as handled!
            unset($sourceHandled[$name]);
        }



        // Duplicate the map to be used as a reverse lookup for determining changes.
        $destinationHandled = $map;

        // Initialize the local destination mapping results.
        $destinationChanges = new SyncChanges();

        // Initialize a duplicate validation list.
        $destinationNames = [];

        // Loop through the provided destination array next...
        foreach($destinationObjects as $class)
        {
            // Get the currently iterated class.
            $currentClass = get_class($class);

            //if($currentClass !== $destinationDefinition->getClass())
            //    throw new \Exception("Class '$currentClass' must be of ClassMap type '{$destinationDefinition->getClass()}'!");

            $destinationKey = $syncClass->getDestinationKey($class);

            if ($destinationKey === "")
                throw new \Exception("Key '$destinationKey' is not in a supported format!");

            $value = $syncClass->getDestinationValue($class);

            /*
            // IF a value not was passed for the "property" argument...
            if($destinationProperty === "")
            {
                // THEN include the entire class as the value, but "unbox" to avoid extraneous properties.
                $value = json_decode(json_encode($class), true);
            }
            else
            {
                // OTHERWISE, use the appropriate getter method to get the specified value.
                $getter = "get" . ucfirst($destinationProperty);

                if (!method_exists($class, $getter))
                    throw new \Exception("A getter of '$getter' could not be found on the class '$class'!");

                $value = $class->{$getter}();
            }
            */

            // Generate a "name" for the mapping, using the provided callback.
            $destinationName = $syncClass->getDestinationName($class); //$destinationDefinition->generateName($class);

            // IF the map already contains this lookup...
            if(in_array($destinationName, $destinationNames))
            {
                // THEN simply add this name to the list of duplicates and continue!
                $destinationChanges->addDuplicated($destinationName);

                // TODO: Determine how we want to handle this, long term!
                continue;
            }

            // Add the current name to the duplicate verification list.
            $destinationNames[] = $destinationName;




            // IF this is the only occurrence of this "name", but it already exists in the map...
            if (array_key_exists($destinationName, $map))
            {
                // AND, IF the map already contains the mapping "key"...
                if(array_key_exists($destinationKey, $map[$destinationName]))
                {
                    // AND IF the "property" value is NOT the same...
                    if($map[$destinationName][$destinationKey] !== $value)
                    {
                        // THEN update the mapping.
                        $map[$destinationName][$destinationKey] = $value;
                        $destinationChanges->addUpdated($destinationName);
                    }

                    // Mark this mapping as handled!
                    unset($destinationHandled[$destinationName]);
                }
                else
                {
                    // OTHERWISE the map contains the "name", but the "key" does not exist!
                    $map[$destinationName][$destinationKey] = $value;
                    $destinationChanges->addCreated($destinationName);

                    // Mark this one as handled!
                    unset($destinationHandled[$destinationName]);
                }
            }
            else
            {
                // OTHERWISE, this "name" does not exist in the map, but could potentially exist under another name!
                $otherName = "";

                // Check for matching "property" values, in case the mapping was created using a different "name"...
                foreach($map as $oldName => $oldMappings)
                {
                    if(array_key_exists($destinationKey, $oldMappings) && $oldMappings[$destinationKey] === $value)
                        $otherName = $oldName;
                }

                // IF a matching "key" => "property" set was found using another "name"...
                if($otherName !== "")
                {
                    // THEN create the new mapping.
                    $map[$destinationName][$destinationKey] = $map[$otherName][$destinationKey];
                    $destinationChanges->addCreated($destinationName);

                    // AND remove the old mapping.
                    unset($map[$otherName][$destinationKey]);
                    $destinationChanges->addDeleted($otherName);

                    // Mark the old one as handled, as the new one would not have been in the list anyways!
                    unset($destinationHandled[$otherName]);
                }
                else
                {
                    // OTHERWISE simply create the mapping!
                    $map[$destinationName] = [];
                    $map[$destinationName][$destinationKey] = $value;
                    $destinationChanges->addCreated($destinationName);

                    // Mark this one as handled!
                    unset($destinationHandled[$destinationName]);
                }
            }
        }

        // Loop through any leftover/unhandled mappings...
        foreach($destinationHandled as $name => $mappings)
        {
            // IF the "key" exists in this mapping...
            if(array_key_exists($destinationKey, $mappings))
            {
                // THEN remove the "key" and add this to the list of "deleted" mappings!
                unset($map[$name][$destinationKey]);
                $destinationChanges->addDeleted($name);
            }

            unset($destinationHandled[$name]);
        }



        // Loop through and remove any empty mappings...
        foreach($map as $name => $mappings)
        {
            $mappingHasSource = array_key_exists($sourceKey, $mappings);
            $mappingHasDestination = array_key_exists($destinationKey, $mappings);

            // IF the mapping has no valid "keys"...
            if(!$mappingHasSource && !$mappingHasDestination)
            {
                // THEN delete it entirely...
                unset($map[$name]);

                // AND remove from both missing sources, as needed!
                $sourceChanges->delMissing($sourceKey);
                $destinationChanges->delMissing($destinationKey);

                continue;
            }

            // IF the mapping contains both "keys"...
            if($mappingHasSource && $mappingHasDestination)
            {
                // THEN remove from both missing sources, as needed!
                $sourceChanges->delMissing($sourceKey);
                $destinationChanges->delMissing($destinationKey);

                continue;
            }

            $inSourceMissing = in_array($name, $sourceChanges->getMissing());
            $inDestinationMissing = in_array($name, $destinationChanges->getMissing());

            // IF the mapping contains only the source 'key"...
            if($mappingHasSource && !$mappingHasDestination)
            {
                // THEN remove the mapping from the missing source list!
                if($inSourceMissing)
                    $sourceChanges->delMissing($name);

                // AND add the mapping to the missing destination list!
                if(!$inDestinationMissing)
                    $destinationChanges->addMissing($name);

                continue;
            }

            // IF the mapping contains only the destination 'key"...
            if(!$mappingHasSource && $mappingHasDestination)
            {
                // THEN add the mapping to the missing source list!
                if(!$inSourceMissing)
                    $sourceChanges->addMissing($name);

                // AND remove the mapping from the missing destination list!
                if($inDestinationMissing)
                    $destinationChanges->delMissing($name);

                continue;
            }

            // TODO: Add any other possible combinations, as needed!
        }





        // IF a map/cache file has been provided...
        if($mapFile !== "")
        {
            // THEN create the directory for the map file, as needed...
            if(!file_exists(dirname($mapFile)))
                mkdir(dirname($mapFile), 0777, true);

            // AND save the results into the map file for later usage.
            //file_put_contents($mapFile, json_encode($map, JSON_PRETTY_PRINT));

            $syncMap = (new SyncMap($mapFile))
                ->setMap($map)
                ->setSourceChanges($sourceChanges)
                ->setDestinationChanges($destinationChanges)
                ->save();
        }
        else
        {
            $syncMap = (new SyncMap())
                ->setMap($map)
                ->setSourceChanges($sourceChanges)
                ->setDestinationChanges($destinationChanges);
        }

        // Finally, return the new mapping!
        return $syncMap;
    }



}