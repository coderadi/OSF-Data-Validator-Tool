<?php

  namespace StructuredDynamics\structwsf\validator\checks; 

  use \StructuredDynamics\structwsf\php\api\ws\sparql\SparqlQuery;
  use \StructuredDynamics\structwsf\php\api\ws\ontology\read\OntologyReadQuery;
  use \StructuredDynamics\structwsf\php\api\ws\ontology\read\GetSubClassesFunction;
  use \StructuredDynamics\structwsf\php\api\ws\crud\read\CrudReadQuery;
  
  
  class CheckOwlRestrictionExact extends Check
  {
    private $classOntologyCache = array();
    
    function __construct()
    { 
      $this->name = 'OWL Exact Cardinality Restriction Check';
      $this->description = 'Make sure that all OWL Exact Cardinality Restriction defined in the ontologies is respected in the datasets';
    }
    
    public function run()
    {
      cecho("\n\n");
      
      cecho("Data validation test: ".$this->description."...\n\n", 'LIGHT_BLUE');

      // Check for Exact Cardinality restriction on Datatype Properties    
      $sparql = new SparqlQuery($this->network);

      $from = '';
      
      foreach($this->checkOnDatasets as $dataset)
      {
        $from .= 'from <'.$dataset.'> ';
      }
      
      foreach($this->checkUsingOntologies as $ontology)
      {
        $from .= 'from named <'.$ontology.'> ';
      }

      $sparql->mime("application/sparql-results+json")
             ->query('select distinct ?restriction ?class ?cardinality ?onProperty ?dataRange
                      '.$from.'
                      where
                      {
                        ?s a ?class.

                        graph ?g {
                            ?class <http://www.w3.org/2000/01/rdf-schema#subClassOf> ?restriction .
                            ?restriction a <http://www.w3.org/2002/07/owl#Restriction> ;
                                         <http://www.w3.org/2002/07/owl#onProperty> ?onProperty ;
                                         <http://www.w3.org/2002/07/owl#qualifiedCardinality> ?cardinality .

                            optional
                            {
                              ?restriction <http://www.w3.org/2002/07/owl#onDataRange> ?dataRange .
                            }     
                        }
                      }')
             ->send();
             
      if($sparql->isSuccessful())
      {
        $results = json_decode($sparql->getResultset(), TRUE);    
        
        if(isset($results['results']['bindings']) && count($results['results']['bindings']) > 0)
        {
          cecho("Here is the list of all the OWL exact cardinality restrictions defined for the classes currently used in the target datasets applied on datatype properties:\n\n", 'LIGHT_BLUE');
          
          $exactCardinalities = array();
          
          foreach($results['results']['bindings'] as $result)
          {
            $class = $result['class']['value'];
            $exactCardinality = $result['cardinality']['value'];
            $onProperty = $result['onProperty']['value'];
            
            $dataRange = '';
            if(isset($result['dataRange']))
            {
              $dataRange = $result['dataRange']['value'];
            }
            
            cecho('  -> Record of type "'.$class.'" have a exact cardinality of "'.$exactCardinality.'" when using datatype property "'.$onProperty.'" with value type "'.$dataRange.'"'."\n", 'LIGHT_BLUE');
            
            if(!isset($exactCardinalities[$class]))
            {
              $exactCardinalities[$class] = array();
            }
            
            $exactCardinalities[$class][] = array(
              'exactCardinality' => $exactCardinality,
              'onProperty' => $onProperty,
              'dataRange' => $dataRange
            );
          }

          cecho("\n\n");

          foreach($exactCardinalities as $class => $exactCards)
          {
            foreach($exactCards as $exactCardinality)
            {
              $sparql = new SparqlQuery($this->network);

              $from = '';
              
              foreach($this->checkOnDatasets as $dataset)
              {
                $from .= 'from <'.$dataset.'> ';
              }
              
              foreach($this->checkUsingOntologies as $ontology)
              {
                $from .= 'from <'.$ontology.'> ';
              }
              
              $datatypeFilter = '';
              
              // Here, if rdfs:Literal is used, we have to filter for xsd:string also since it is the default
              // used by Virtuoso...
              if(!empty($exactCardinality['dataRange']))
              {
                if($exactCardinality['dataRange'] == 'http://www.w3.org/2000/01/rdf-schema#Literal')
                {
                  $datatypeFilter = 'filter((str(datatype(?value)) = \'http://www.w3.org/2000/01/rdf-schema#Literal\' || str(datatype(?value)) = \'http://www.w3.org/2001/XMLSchema#string\') && ?onProperty in (<'.$exactCardinality['onProperty'].'>))';
                }
                else
                {
                  $datatypeFilter = 'filter((str(datatype(?value)) = \''.$exactCardinality['dataRange'].'\') && ?onProperty in (<'.$exactCardinality['onProperty'].'>))';
                }
              }
              
              $sparql->mime("application/sparql-results+json")
                     ->query('select ?s ?onProperty count(?onProperty) as ?nb_values
                              '.$from.'
                              where
                              {
                                ?s a <'.$class.'> ;
                                   ?onProperty ?value .

                                '.$datatypeFilter.'
                              }
                              group by ?s ?onProperty
                              having(count(?onProperty) != '.$exactCardinality['exactCardinality'].')                            
                             ')
                     ->send();

              if($sparql->isSuccessful())
              {
                $results = json_decode($sparql->getResultset(), TRUE);    
                
                if(isset($results['results']['bindings']) && count($results['results']['bindings']) > 0)
                {
                  foreach($results['results']['bindings'] as $result)
                  {
                    $subject = $result['s']['value'];
                    $numberOfOccurences = $result['nb_values']['value'];
                    
                    cecho('  -> record: '.$subject."\n", 'LIGHT_RED');
                    cecho('     -> property: '.$exactCardinality['onProperty']."\n", 'LIGHT_RED');
                    cecho('        -> number of occurences: '.$numberOfOccurences."\n", 'LIGHT_RED');
                    
                    $this->errors[] = array(
                      'id' => 'OWL-RESTRICTION-EXACT-100',
                      'type' => 'error',
                      'invalidRecordURI' => $subject,
                      'invalidPropertyURI' => $exactCardinality['onProperty'],
                      'numberOfOccurences' => $numberOfOccurences,
                      'exactExpectedNumberOfOccurences' => $exactCardinality['exactCardinality'],
                      'dataRange' => $exactCardinality['dataRange']
                    );                  
                  }
                }
              }
              else
              {
                cecho("We couldn't get the number of properties used per record from the structWSF instance\n", 'YELLOW');
                
                // Error: cam't get the number of properties used per record
                $this->errors[] = array(
                  'id' => 'OWL-RESTRICTION-EXACT-51',
                  'type' => 'warning',
                );                     
              }
              
              // Check the edgecase where the entities are not using this property (so, the previous sparql query won't bind)
              if($exactCardinality['onProperty'] > 0)
              {              
                $sparql->mime("application/sparql-results+json")
                       ->query('select ?s
                                '.$from.'
                                where
                                {
                                  ?s a <'.$class.'> .
                                  
                                  filter not exists {
                                    ?s <'.$exactCardinality['onProperty'].'> ?value .
                                  }
                                }
                               ')
                       ->send();

                if($sparql->isSuccessful())
                {
                  $results = json_decode($sparql->getResultset(), TRUE);    
                  
                  if(isset($results['results']['bindings']) && count($results['results']['bindings']) > 0)
                  {
                    foreach($results['results']['bindings'] as $result)
                    {
                      $subject = $result['s']['value'];
                      
                      cecho('  -> record: '.$subject."\n", 'LIGHT_RED');
                      cecho('     -> property: '.$exactCardinality['onProperty']."\n", 'LIGHT_RED');
                      cecho('        -> number of occurences: 0'."\n", 'LIGHT_RED');
                      
                      $this->errors[] = array(
                        'id' => 'OWL-RESTRICTION-EXACT-102',
                        'type' => 'error',
                        'invalidRecordURI' => $subject,
                        'invalidPropertyURI' => $exactCardinality['onProperty'],
                        'numberOfOccurences' => '0',
                        'exactExpectedNumberOfOccurences' => $exactCardinality['exactCardinality'],
                        'dataRange' => $exactCardinality['dataRange']
                      );                  
                    }
                  }                
                } 
                else
                {
                  cecho("We couldn't check if the properties were defined on the records on the structWSF instance\n", 'YELLOW');
  
                  // Error: can't get the list of retrictions
                  $this->errors[] = array(
                    'id' => 'OWL-RESTRICTION-EXACT-52',
                    'type' => 'warning',
                  ); 
                }   
              }           
            }
          }
          
          if(count($this->errors) <= 0)
          {
            cecho("\n\n  All records respects the exact restrictions cardinality specified in the ontologies...\n\n\n", 'LIGHT_GREEN');
          }           
        }
        else
        {
          cecho("No classes have any exact restriction cardinality defined in any ontologies. Move on to the next check...\n\n\n", 'LIGHT_GREEN');
        }
      }
      else
      {
        cecho("We couldn't get list of restrictions from the structWSF instance\n", 'YELLOW');
        
        // Error: can't get the list of retrictions
        $this->errors[] = array(
          'id' => 'OWL-RESTRICTION-EXACT-50',
          'type' => 'warning',
        );                     
      }
      
      // Check for exact Cardinality restriction on Object Properties      
      $sparql = new SparqlQuery($this->network);

      $from = '';
      
      foreach($this->checkOnDatasets as $dataset)
      {
        $from .= 'from <'.$dataset.'> ';
      }
      
      foreach($this->checkUsingOntologies as $ontology)
      {
        $from .= 'from named <'.$ontology.'> ';
      }

      $sparql->mime("application/sparql-results+json")
             ->query('select distinct ?restriction ?class ?cardinality ?onProperty ?classExpression
                      '.$from.'
                      where
                      {
                        ?s a ?class.

                        graph ?g {
                            ?class <http://www.w3.org/2000/01/rdf-schema#subClassOf> ?restriction .
                            ?restriction a <http://www.w3.org/2002/07/owl#Restriction> ;
                                         <http://www.w3.org/2002/07/owl#onProperty> ?onProperty ;
                                         <http://www.w3.org/2002/07/owl#cardinality> ?cardinality .
                                         
                          optional
                          {
                            ?restriction <http://www.w3.org/2002/07/owl#onClass> ?classExpression .
                          }                                            
                        }
                      }')
             ->send();
             
      if($sparql->isSuccessful())
      {
        $results = json_decode($sparql->getResultset(), TRUE);    
        
        if(isset($results['results']['bindings']) && count($results['results']['bindings']) > 0)
        {
          cecho("Here is the list of all the OWL exact cardinality restrictions defined for the classes currently used in the target datasets applied on object properties:\n\n", 'LIGHT_BLUE');
          
          $exactCardinalities = array();
          
          foreach($results['results']['bindings'] as $result)
          {
            $class = $result['class']['value'];
            $exactCardinality = $result['cardinality']['value'];
            $onProperty = $result['onProperty']['value'];
            
            $classExpression = '';
            if(isset($result['classExpression']))
            {
              $classExpression = $result['classExpression']['value'];
            }            
            
            cecho('  -> Record of type "'.$class.'" have a exact cardinality of "'.$exactCardinality.'" when using object property "'.$onProperty.'"'."\n", 'LIGHT_BLUE');
            
            if(!isset($exactCardinalities[$class]))
            {
              $exactCardinalities[$class] = array();
            }
            
            $exactCardinalities[$class][] = array(
              'exactCardinality' => $exactCardinality,
              'onProperty' => $onProperty,
              'classExpression' => $classExpression
            );
          }

          cecho("\n\n");

          foreach($exactCardinalities as $class => $exactCards)
          {
            foreach($exactCards as $exactCardinality)
            {
              $sparql = new SparqlQuery($this->network);

              $from = '';
              
              foreach($this->checkOnDatasets as $dataset)
              {
                $from .= 'from <'.$dataset.'> ';
              }
              
              foreach($this->checkUsingOntologies as $ontology)
              {
                $from .= 'from <'.$ontology.'> ';
              }
              
              $classExpressionFilter = '';
              if(!empty($exactCardinality['classExpression']) && $exactCardinality['classExpression'] != 'http://www.w3.org/2002/07/owl#Thing')
              {
                $subClasses = array($exactCardinality['classExpression']);
                
                // Get all the classes that belong to the class expression defined in this restriction
                $getSubClassesFunction = new GetSubClassesFunction();
                
                $getSubClassesFunction->getClassesUris()
                                      ->allSubClasses()
                                      ->uri($exactCardinality['classExpression']);
                                        
                $ontologyRead = new OntologyReadQuery($this->network);
                
                $ontologyRead->ontology($this->getClassOntology($exactCardinality['classExpression']))
                             ->getSubClasses($getSubClassesFunction)
                             ->enableReasoner()
                             ->mime('resultset')
                             ->send();
                             
                if($ontologyRead->isSuccessful())
                {
                  $resultset = $ontologyRead->getResultset()->getResultset();
                  
                  $subClasses = array_merge($subClasses, array_keys($resultset['unspecified']));
                  
                  if(!empty($subClasses))
                  {
                    $classExpressionFilter .= " filter(?value_type in (<".implode(">, <", $subClasses).">)) \n";
                  }
                }
                else
                {
                  // error
                }
              }                      
              
              $sparql->mime("application/sparql-results+json")
                     ->query('select ?s ?onProperty count(?onProperty) as ?nb_values
                              '.$from.'
                              where
                              {
                                ?s a <'.$class.'> ;
                                   ?onProperty ?value .
                                   
                                ?value a ?value_type .   

                                '.$classExpressionFilter.'
                                   
                                filter(?onProperty in (<'.$exactCardinality['onProperty'].'>)) .
                              }
                              group by ?s ?onProperty
                              having(count(?onProperty) != '.$exactCardinality['exactCardinality'].')                            
                             ')
                     ->send();
                     
              if($sparql->isSuccessful())
              {
                $results = json_decode($sparql->getResultset(), TRUE);    
                
                if(isset($results['results']['bindings']) && count($results['results']['bindings']) > 0)
                {
                  foreach($results['results']['bindings'] as $result)
                  {
                    $subject = $result['s']['value'];
                    $numberOfOccurences = $result['nb_values']['value'];
                    
                    cecho('  -> record: '.$subject."\n", 'LIGHT_RED');
                    cecho('     -> property: '.$exactCardinality['onProperty']."\n", 'LIGHT_RED');
                    cecho('        -> number of occurences: '.$numberOfOccurences."\n", 'LIGHT_RED');
                    
                    $this->errors[] = array(
                      'id' => 'OWL-RESTRICTION-EXACT-101',
                      'type' => 'error',
                      'invalidRecordURI' => $subject,
                      'invalidPropertyURI' => $exactCardinality['onProperty'],
                      'numberOfOccurences' => $numberOfOccurences,
                      'exactExpectedNumberOfOccurences' => $exactCardinality['exactCardinality'],
                      'classExpression' => $exactCardinality['classExpression']
                    );                  
                  }
                }
              }
              
              // Check the edgecase where the entities are not using this property (so, the previous sparql query won't bind)
              if($exactCardinality['onProperty'] > 0)
              {
                $sparql->mime("application/sparql-results+json")
                       ->query('select ?s
                                '.$from.'
                                where
                                {
                                  ?s a <'.$class.'> .
                                  
                                  filter not exists {
                                    ?s <'.$exactCardinality['onProperty'].'> ?value .
                                  }
                                }
                               ')
                       ->send();

                if($sparql->isSuccessful())
                {
                  $results = json_decode($sparql->getResultset(), TRUE);    
                  
                  if(isset($results['results']['bindings']) && count($results['results']['bindings']) > 0)
                  {
                    foreach($results['results']['bindings'] as $result)
                    {
                      $subject = $result['s']['value'];
                      
                      cecho('  -> record: '.$subject."\n", 'LIGHT_RED');
                      cecho('     -> property: '.$exactCardinality['onProperty']."\n", 'LIGHT_RED');
                      cecho('        -> number of occurences: 0'."\n", 'LIGHT_RED');
                      
                      $this->errors[] = array(
                        'id' => 'OWL-RESTRICTION-EXACT-104',
                        'type' => 'error',
                        'invalidRecordURI' => $subject,
                        'invalidPropertyURI' => $exactCardinality['onProperty'],
                        'numberOfOccurences' => '0',
                        'exactExpectedNumberOfOccurences' => $exactCardinality['exactCardinality'],
                        'classExpression' => $exactCardinality['classExpression']
                      );                  
                    }
                  }                
                } 
                else
                {
                  cecho("We couldn't check if the properties were defined on the records on the structWSF instance\n", 'YELLOW');
                  
                  // Error: can't get the list of retrictions
                  $this->errors[] = array(
                    'id' => 'OWL-RESTRICTION-EXACT-53',
                    'type' => 'warning',
                  ); 
                }     
              }         
            }
            
            if(count($this->errors) > 0)
            {
  //            cecho("\n\n  Note: All the errors returned above list records that are being described using not enough of a certain type of property. The ontologies does specify that a minimum cardinality should be used for these properties, and what got indexed in the system goes against this instruction of the ontology.\n\n\n", 'LIGHT_RED');
            }
            else
            {
  //            cecho("\n\n  All properties respects the minimum cardinality specified in the ontologies...\n\n\n", 'LIGHT_GREEN');
            }           
          }
        }
        else
        {
//          cecho("No properties have any minimum cardinality defined in any ontologies. Move on to the next check...\n\n\n", 'LIGHT_GREEN');
        }
      } 
      else
      {
        cecho("We couldn't get the cardinality restriction on the object property from the structWSF instance\n", 'YELLOW');
        
        $this->errors[] = array(
          'id' => 'OWL-RESTRICTION-EXACT-54',
          'type' => 'warning',
        );           
      }     
    }
    
    public function outputXML()
    {
      if(count($this->errors) <= 0)
      {
        return('');
      }
      
      $xml = "  <check>\n";
      
      $xml .= "    <name>".$this->name."</name>\n";      
      $xml .= "    <description>".$this->description."</description>\n";      
      $xml .= "    <onDatasets>\n";
      
      foreach($this->checkOnDatasets as $dataset)
      {
        $xml .= "      <dataset>".$dataset."</dataset>\n";
      }
      
      $xml .= "    </onDatasets>\n";

      $xml .= "    <usingOntologies>\n";
      
      foreach($this->checkUsingOntologies as $ontology)
      {
        $xml .= "      <ontology>".$ontology."</ontology>\n";
      }
      
      $xml .= "    </usingOntologies>\n";
      
      $xml .= "    <validationWarnings>\n";
      
      foreach($this->errors as $error)
      {
        if($error['type'] == 'warning')
        {
          $xml .= "      <warning>\n";
          $xml .= "        <id>".$error['id']."</id>\n";
          $xml .= "      </warning>\n";
        }
      }
      
      $xml .= "    </validationWarnings>\n";      
                                
      $xml .= "    <validationErrors>\n";
      
      foreach($this->errors as $error)
      {
        if($error['type'] == 'error')
        {
          $xml .= "      <error>\n";
          $xml .= "        <id>".$error['id']."</id>\n";
          $xml .= "        <invalidRecordURI>".$error['invalidRecordURI']."</invalidRecordURI>\n";
          $xml .= "        <invalidPropertyURI>".$error['invalidPropertyURI']."</invalidPropertyURI>\n";
          $xml .= "        <numberOfOccurences>".$error['numberOfOccurences']."</numberOfOccurences>\n";
          
          if(!empty($error['exactExpectedNumberOfOccurences']))
          {
            $xml .= "        <exactExpectedNumberOfOccurences>".$error['exactExpectedNumberOfOccurences']."</exactExpectedNumberOfOccurences>\n";
          }
          
          if(!empty($error['dataRange']))
          {
            $xml .= "        <dataRange>".$error['dataRange']."</dataRange>\n";
          }
          
          if(!empty($error['classExpression']))
          {
            $xml .= "        <classExpression>".$error['classExpression']."</classExpression>\n";
          }
          
          $xml .= "      </error>\n";
        }
      }
      
      $xml .= "    </validationErrors>\n";
      
      $xml .= "  </check>\n";
      
      return($xml);
    }    
    
    public function outputJSON()
    {
      if(count($this->errors) <= 0)
      {
        return('');
      }
      
      $json = "  {\n";
      
      $json .= "    \"name\": \"".$this->name."\",\n";      
      $json .= "    \"description\": \"".$this->description."\",\n";      
      $json .= "    \"onDatasets\": [\n";
      
      foreach($this->checkOnDatasets as $dataset)
      {
        $json .= "      \"".$dataset."\",\n";
      }
      
      $json = substr($json, 0, strlen($json) - 2)."\n";
      
      $json .= "    ],\n";

      $json .= "    \"usingOntologies\": [\n";
      
      foreach($this->checkUsingOntologies as $ontology)
      {
        $json .= "      \"".$ontology."\",\n";
      }

      $json = substr($json, 0, strlen($json) - 2)."\n";
      
      $json .= "    ],\n";
      
      $json .= "    \"validationWarnings\": [\n";
      
      $foundWarnings = FALSE;
      foreach($this->errors as $error)
      {
        if($error['type'] == 'warning')
        {
          $json .= "      {\n";
          $json .= "        \"id\": \"".$error['id']."\"\n";
          $json .= "      },\n";
          
          $foundWarnings = TRUE;
        }
      }
      
      if($foundWarnings)
      {
        $json = substr($json, 0, strlen($json) - 2)."\n";
      }     
      
      $json .= "    ],\n";      
      
      $json .= "    \"validationErrors\": [\n";
                             
      $foundErrors = FALSE;
      foreach($this->errors as $error)
      {
        if($error['type'] == 'error')
        {
          $json .= "      {\n";
          $json .= "        \"id\": \"".$error['id']."\",\n";
          $json .= "        \"invalidRecordURI\": \"".$error['invalidRecordURI']."\",\n";
          $json .= "        \"invalidPropertyURI\": \"".$error['invalidPropertyURI']."\",\n";
          $json .= "        \"numberOfOccurences\": \"".$error['numberOfOccurences']."\",\n";

          if(!empty($error['exactExpectedNumberOfOccurences']))
          {
            $json .= "        \"exactExpectedNumberOfOccurences\": \"".$error['exactExpectedNumberOfOccurences']."\",\n";
          }
          
          if(!empty($error['dataRange']))
          {
            $json .= "        \"dataRange\": \"".$error['dataRange']."\",\n";
          }
          
          if(!empty($error['classExpression']))
          {
            $json .= "        \"classExpression\": \"".$error['classExpression']."\",\n";
          }
          
          $json = substr($json, 0, strlen($json) - 2)."\n";

          $json .= "      },\n";
          
          $foundErrors = TRUE;
        }
      }
      
      if($foundErrors)
      {
        $json = substr($json, 0, strlen($json) - 2)."\n";
      }
      
      $json .= "    ]\n";
      
      $json .= "  }\n";
      
      return($json);      
    }  
    
    private function getClassOntology($class) 
    {
      if(isset($this->classOntologyCache[$class]))      
      {
        return($class);
      }
      else
      {
        $crudRead = new CrudReadQuery($this->network);
        
        $classes = array();
        
        foreach($this->checkUsingOntologies as $ontology)
        {
          $classes[] = $class;
        }
        
        $crudRead->uri($classes)
                 ->dataset($this->checkUsingOntologies)
                 ->mime('resultset')
                 ->send();
                 
        if($crudRead->isSuccessful())
        {
          $resultset = $crudRead->getResultset()->getResultset();
    
          $this->classOntologyCache[key($resultset)] = key($resultset);
    
          return($this->classOntologyCache[key($resultset)]);
        }
        else
        {
          return(FALSE);
        }
      }
    }
  }
?>
