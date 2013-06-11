<?php exit; ?>

row: 0

    field: title
        Generate the list used when navigating the reports.
    field;
    
    field: content
        <?php
            use Cms\Enumerations\FieldType;
            
            function distance($lat1, $lon1, $lat2, $lon2, $unit='M') 
            {
                $theta = $lon1 - $lon2; 
                $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta)); 
                $dist = acos($dist); 
                $dist = rad2deg($dist); 
                $miles = $dist * 60 * 1.1515;
                $unit = strtoupper($unit);
             
                if($unit == "K")
                {
                    return ($miles * 1.609344); 
                }
                elseif($unit == "N") {
                    return ($miles * 0.8684);
                }
                else
                {
                    return $miles;
                }
            }
            
            $page = intval($_REQUEST['page']);
            
            if($page == 0)
                $page = 1;
            
            $amount = 10;
            if(strlen(trim($_REQUEST['amount'])) > 0)
            {
                $_REQUEST['amount'] = intval($_REQUEST['amount']);
                
                if($_REQUEST['amount'] <= 30 && $_REQUEST['amount'] > 0)
                    $amount = $_REQUEST['amount'];
            }
            
            $by_city = false;
            
            $select = new Cms\DBAL\Query\Select('deficiencies');
            $select->SelectAll();
            
            $select_count = new Cms\DBAL\Query\Count('deficiencies', 'id', 'reports_count');
            
            if(trim($_REQUEST['city']))
            {
                $by_city = true;
                
                $select->WhereEqual('city', $_REQUEST['city'], FieldType::TEXT);
                $select_count->WhereEqual('city', $_REQUEST['city'], FieldType::TEXT);
            }
            
            if(strlen(trim($_REQUEST['type'])) > 0)
            {
                $select->WhereEqual('type', $_REQUEST['type'], FieldType::INTEGER);
                $select_count->WhereEqual('type', $_REQUEST['type'], FieldType::INTEGER);
            }
            
            if(!$by_city)
            {
                if(trim($_REQUEST['lon']) && trim($_REQUEST['lat']))
                {
                    $lat = doubleval($_REQUEST['lat']);
                    $lon = doubleval($_REQUEST['lon']);
                    
                    //Less precise but faster
                    //$select->OrderByCustom("abs((latitude+longitude+172)-($lat+$lon+172))");
                    
                    //More precise but slower
                    $select->OrderByCustom("distance(latitude, longitude, $lat, $lon)");
                }
            }
            else
            {
                $select->OrderBy('report_timestamp', \Cms\Enumerations\Sort::DESCENDING);
            }
            
            $limit_start = 0;
            
            if($page > 1)
                $limit_start = ($page-1) * $amount;
            
            $select->Limit($limit_start, $amount);
            
            $db = \Cms\System::GetRelationalDatabase();
            
            $db->pdo->sqliteCreateFunction("distance", 'distance', 4);
            
            //Count results
            $db->Count($select_count);
            
            $result = $db->Fetch();
            
            $count = $result['reports_count'];
            
            //Calculate pages
            $pages = 1;
            
            if($amount < $count)
            {
                $pages = floor($count / $amount);
                
                if(($count % $amount) > 0)
                    $pages++;
            }
            
            //Return results
            //print $select->GetSQL(\Cms\DBAL\DataSource::SQLITE);
            
            $db->Select($select);
            
            $cities = array_flip(Deficiencies\Towns::GetAll());
            
            $reports = array();
            $reports_returned = 0;
            while($result = $db->FetchArray())
            {
                $result['city'] = $cities[$result['city']];
                $result['age'] = Cms\Utilities::GetTimeElapsed($result["report_timestamp"]);
                $result['type_str'] = Deficiencies\DeficiencyTypes::getType($result['type']);
                $reports[] = $result;
                
                $reports_returned++;
            }
            
            $output = array(
                'reports'=>$reports,
                'stats'=>array(
                    'current_page'=>$page,
                    'total_reports'=>$count,
                    'total_pages'=>$pages,
                    'amount_returned'=>$reports_returned,
                )
            );
            
            print Cms\Utilities\Json::Encode($output);
        ?>
    field;
    
    field: rendering_mode
        api
    field;
    
row;