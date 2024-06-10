<?php
    /** include libraries */
    require('../bootstrap.php');
    Bootstrap::loadConfig();
    Bootstrap::defineTables(TABLE_PREFIX);
    Bootstrap::i18n_prep();
    Bootstrap::loadCode();
    Bootstrap::connect();
    date_default_timezone_set("Asia/Bangkok");

    $ticket_id  = $_POST['ticket_id'];
    $form_id    = $_POST['form_id'];
    $field_id   = $_POST['field_id'];
    $username   = $_POST['username'];
    $new_value  = $_POST['new_value'];
    $old_value  = $_POST['old_value'];
    $ctype      = strtolower($_POST['ctype']);

    /** add a thread event */
    $thread_id   = '';      
    $thread_type = '';          
    $event_id    = '';    //4=assigned  9=edited
    $staff_id    = '';      
    $team_id     = '';      
    $dept_id     = '';      
    $topic_id    = '';      
    $data        = '';  
    $username    = $username;      
    $uid         = '';  
    $uid_type    = 'S';      
    $annulled    = '0';      
    $timestamp   = date('Y-m-d H:i:s');   
    $log_event   = false;

    if($ctype=='department'){
        $sql        = "update sup_ticket set dept_id='$new_value' where ticket_id='$ticket_id'";
        $res        = db_query($sql);
    }
    else if($ctype=='staff'){
        $sql        = "update sup_ticket set staff_id='$new_value' where ticket_id='$ticket_id'";
        $res        = db_query($sql);

        $sql        = "select * from sup_staff where username='$username' and staff_id='$new_value'";
        if(($res=db_query($sql, false)) && db_num_rows($res)){
            //new_value == username => 'claimed'
            $data   = json_encode(array( "claim"=>true ));
        }
        else{
            $sql    = "select concat(firstname,' ',lastname) staff_name from sup_staff where staff_id='$new_value'";
            $res    = db_assoc_array(db_query($sql));
            //{"staff":[2,"Korn Phonlawat"]}
            $data   = json_encode(array( "staff"=>array( $new_value, $res[0]['staff_name'] ) ) );
        }
        $event_id   = '4';    //4=assigned  9=edited
        $log_event  = true;
    }
    else{
        /** update form entry value */
        //get entry id
        $entry_id   = '';
        $sql        = "select id entry_id from sup_form_entry where object_id='$ticket_id' and id=$form_id";
        $res        = db_query($sql);
        $form_entry = db_assoc_array($res);
        foreach($form_entry as $entry)
        {
            $entry_id = $entry['entry_id'];
        }
        // var_dump( '$new_value::',$new_value, '$entry_id::',$entry_id );

        if($ctype=='choices'){
            //{"0":"Request","1":"{\"2\":\"Issue\"}","fields":{"60":["{\"1\":\"Request\"}","{\"2\":\"Issue\"}"]}}
            $new        = explode( ':', $new_value );
            $set_new    = json_encode(array(            
                                $new[0] => $new[1]
                        ));
            $old        = explode( ':', $old_value );
            $set_old    = json_encode(array(            
                                $old[0] => $old[1]
                        ));
            $old_value  = $old[1];
            $set_old    = str_replace('"','\"',$set_old);
            $set_new    = str_replace('"','\"',$set_new);
        }
        else if($ctype=='text'||$ctype=='memo'){
            //{"0":"Singing S2","1":"Singing","fields":{"46":["Singing S2","Singing"]}}
            $set_old    = $old_value;
            $set_new    = $new_value;
        }

        // update value
        $sql        = "update sup_form_entry_values set value ='$set_new' where entry_id=$entry_id and field_id=$field_id";
        $res        = db_query($sql);
        
        // prepare log event
        if($ctype=='memo'){
            /*
                {
                    "0":"Date Time Venue PCS ICT  Mondays 12th June 07:40am - 12:40pm QEII x22 long white tables with x6 chairs around each table for student groups (stage can remain as set up for other events) ICT support ...",
                    "1":"Date Time Venue PCS ICT  Monday 12th June 07:40am - 12:40pm QEII x22 long white tables with x6 chairs around each table for student groups (stage can remain as set up for other events) ICT support ...",
                    "fields":{
                        "50":["Date Time Venue PCS ICT  Mondays 12th June 07:40am - 12:40pm QEII x22 long white tables with x6 chairs around each table for student groups (stage can remain as set up for other events) ICT support ...","Date Time Venue PCS ICT  Monday 12th June 07:40am - 12:40pm QEII x22 long white tables with x6 chairs around each table for student groups (stage can remain as set up for other events) ICT support ..."]
                    }
                }
            */
            $length     = 100;

            $set_old    = strip_tags($old_value);
            $set_old    = (strlen($set_old) > $length) ? substr($set_old,0,$length).'...' : $set_old;
            $old_value  = $set_old;

            $set_new    = strip_tags($new_value);
            $set_new    = (strlen($set_new) > $length) ? substr($set_new,0,$length).'...' : $set_new;
        }

        $data = json_encode(array(
            0 => $old_value,
            1 => $set_new,
            'fields' => array(
                $field_id => array( $set_old, $set_new)
            )
        ));
        $event_id   = '9';    //4=assigned  9=edited
        $log_event  = true;
    }
     
    if($log_event){
        //update event log
        $sql         = "select 
                            sup_thread.id 'thread_id', sup_thread.object_type 'thread_type', 
                            sup_ticket.staff_id, sup_ticket.team_id, sup_ticket.dept_id, sup_ticket.topic_id, 
                            sup_staff.staff_id uid, sup_staff.username
                        from 
                            sup_ticket left join
                            sup_thread on sup_thread.object_id = sup_ticket.ticket_id,
                            sup_staff
                        where
                            sup_ticket.ticket_id ='$ticket_id' and sup_staff.username='$username';";
        $res         = db_query($sql);
        $sup_ticket  = db_assoc_array($res);
        foreach($sup_ticket as $ticket)
        {
            $thread_id   = $ticket['thread_id'];      
            $thread_type = $ticket['thread_type']; 
            $staff_id    = $ticket['staff_id'];      
            $team_id     = $ticket['team_id'];      
            $dept_id     = $ticket['dept_id'];      
            $topic_id    = $ticket['topic_id'];
            $uid         = $ticket['uid'];  
        }
        $sql         = "insert into
                        sup_thread_event
                            (
                                thread_id,thread_type,event_id,staff_id,team_id,dept_id,topic_id,data,username,uid,uid_type,annulled,timestamp
                            )
                        values
                            (
                                $thread_id,'$thread_type',$event_id,$staff_id,$team_id,$dept_id,$topic_id,'$data','$username',$uid,'$uid_type',$annulled,'$timestamp'
                            );
                    ";
                                
        $res         = db_query($sql);
    
        /* update time to match with thread */
        $sql         = sprintf("update 
                                    sup_ticket
                                set
                                    updated = '%s'
                                where
                                    ticket_id = %s;",
                                $timestamp,
                                $ticket_id 
                        );
        $res         = db_query($sql);
    }
    
    echo 'done'; 
?>