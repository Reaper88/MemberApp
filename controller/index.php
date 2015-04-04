<?php
/**
 * Member Application Extension for Phpbb Forums
 * Copyright (C) 2015  Kevin Roy
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 * 
 */
namespace reaper\memberapp\controller;

//use \phpbb\auth\auth;
use \phpbb\db\driver\driver_interface;
//use \Symfony\Component\DependencyInjection\ContainerInterface;
use \phpbb\controller\helper;
use \phpbb\template\template;
use \phpbb\user;
use phpbb\request\request;

class index {


    /** @var \phpbb\db\driver\driver_interface */
    protected $db;

    /** @var \phpbb\request\request */
    protected $request;

    /** @var \phpbb\controller\helper */
    protected $helper;

    /** @var \phpbb\template\template */
    protected $template;

    /** @var \phpbb\user */
    protected $user;
    
    /** @var  phpbb root path */
    protected $root_path;
    
    /** @var php extension */
    protected  $php_ext;

    /**
     * Constructor
     *
     * @param \phpbb\db\driver\driver_interface     $db         Database object
     * @param \phpbb\controller\helper              $helper     Controller helper object
     * @param \phpbb\template\template              $template   Template object
     * @param \phpbb\user                           $user       User object
     * @return \reaper\memberapp\controller\index
     * @access public
     */
    public function __construct(driver_interface $db, helper $helper, request $request, template $template, user $user, $phpbb_root_path, $php_ext) {
        $this->db = $db;
        $this->helper = $helper;
        $this->request = $request;
        $this->template = $template;
        $this->user = $user;
        $this->root_path = $phpbb_root_path;
        $this->php_ext = $php_ext;
        include_once $this->root_path . 'includes/functions_posting.php';
    }

    public function index() {
        return $this->helper->render('index.html');
    }

    public function post() {
        if ($this->request->is_set_post('submit')) {
            $this->generate_topic();
            return $this->helper->render('post.html', 'Posting');
        } else {
            return redirect('/forums/memberapp');
        }
    }

    private function generate_topic() {
        if($this->request->is_set_post('submit')){
            $post = $this->request->get_super_global( \phpbb\request\request_interface::POST);
            $this->user->add_lang_ext('reaper/memberapp', 'exceptions');
            if($post['rules'] === 'No'){
                throw new \reaper\memberapp\exception\base($this->user->lang('EXCEPTION_RULES'));
            } elseif($post['rules'] === 'Yes') {
            $message = $this->prepare_body($post);
            $errors = \generate_text_for_storage($message, $uid, $bitfield, $flag , true, true, true);

            if(\sizeof($errors)){
                // Errors occured, show them to the user.
                // PARSE_ERRORS variable must be defined in the template
                $template->assign_vars(array(
                    'PARSE_ERRORS'      => implode('<br>', $errors),
                ));

            }else{
                $sql_array = [
                    // General Posting Settings
                    'forum_id'            => 107,    // The forum ID in which the post will be placed. (int)
                    'topic_id'            => 0,    // Post a new topic or in an existing one? Set to 0 to create a new one, if not, specify your topic ID here instead.
                    'icon_id'            => false,    // The Icon ID in which the post will be displayed with on the viewforum, set to false for icon_id. (int)

                    // Defining Post Options
                    'enable_bbcode'    => true,    // Enable BBcode in this post. (bool)
                    'enable_smilies'    => true,    // Enabe smilies in this post. (bool)
                    'enable_urls'        => true,    // Enable self-parsing URL links in this post. (bool)
                    'enable_sig'        => true,    // Enable the signature of the poster to be displayed in the post. (bool)

                    // Message Body
                    'message'            => $message,        // Your text you wish to have submitted. It should pass through generate_text_for_storage() before this. (string)
                    'message_md5'    => md5($message),// The md5 hash of your message

                    // Values from generate_text_for_storage()
                    'bbcode_bitfield'    => $bitfield,    // Value created from the generate_text_for_storage() function.
                    'bbcode_uid'        => $uid,        // Value created from the generate_text_for_storage() function.

                    // Other Options
                    'post_edit_locked'    => 1,        // Disallow post editing? 1 = Yes, 0 = No
                    'topic_title'        => '-M-embership application from ' . $this->user->data['username'],    // Subject/Title of the topic. (string)

                    // Email Notification Settings
                    'notify_set'        => false,        // (bool)
                    'notify'            => false,        // (bool)
                    'post_time'         => 0,        // Set a specific time, use 0 to let submit_post() take care of getting the proper time (int)
                    'forum_name'        => '-M-embership application',        // For identifying the name of the forum in a notification email. (string)

                    // Indexing
                    'enable_indexing'    => true,        // Allow indexing the post? (bool)

                    // 3.0.6
                    'force_approved_state'    => true, // Allow the post to be submitted without going into unapproved queue

                    // 3.1-dev, overwrites force_approve_state
                    'force_visibility'            => true, // Allow the post to be submitted without going into unapproved queue, or make it be deleted
                ];
                \submit_post('post', '-M-embership application from ' . $this->user->data['username'], $username, \POST_NORMAL, $poll, $sql_array);
                return;
                }
            }
        }
        return redirect('/forums/memberapp');
    }

    public function get_next_topic_id() {
        $sql = 'SELECT topic_id FROM ' . TOPICS_TABLE . ' ORDER BY topic_id DESC LIMIT 1;';
            /* @var $last_topic_id int */
            $result = $this->db->sql_query($sql);
            $row = $this->db->sql_fetchrow($result);
            $this->db->sql_freeresult($result);
            
            if($row) {
                $last_topic_id = (int) $row['topic_id'];
                $next_topic_id = ++$last_topic_id;
            }
            return $next_topic_id;
    }
    public function get_next_post_id() {
         $sql = 'SELECT post_id FROM ' . POSTS_TABLE . ' ORDER BY post_id DESC LIMIT 1;';
           /* @var $last_post_id int */
            $result = $this->db->sql_query($sql);
            $row = $this->db->sql_fetchrow($result);
            $this->db->sql_freeresult($result);
            
            if($row) {
                $last_post_id = (int) $row['post_id'];
                $next_post_id = ++$last_post_id;
            }
            return $next_post_id;
    }

    public function prepare_body($post) {
        unset($post['submit'], $post['rules']);
        $body = 'Real Name: ' . $post['flname'] . '<br />'
                . 'Game Name: ' . $post['gamename'] . '<br />'
                . 'Age: ' . $post['age'] .'<br />'
                . 'Location: ' . $post['location'] . '<br />'
                . 'Steam Username: ' . $post['steam'] . '<br />'
                . 'Origin Username: ' . $post['origin'] . '<br />'
                . '-M- Friends: ' . $post['mfriend'] . '<br />'
                . 'Past & Future Games: ' . $post['games'] . '<br />'
                . 'Arts & Coding Skills: ' . $post['arts'] . '<br />'
                . 'PC/System Tech: ' . $post['occupation'] . '<br />'
                . 'Online Time: ' . $post['onlinetime'] . '<br />'
                . 'How he found us: ' . $post['found'];
        return $body;
    }

    public function generate_post($next_topic_id, $body) {
        $sql_array = [
            'post_id'   => null,
            'topic_id'  => $next_topic_id,
            'forum_id'  => (int) 107,
            'poster_id' => $this->user->data['user_id'],
            'poster_ip' => $this->user->ip,
            'post_time' => time(),
            'post_subject'  => '-M-embershit Application from '.$this->user->data['username'],
            'post_text' => $body,
            'post_checksum' => \md5($body),
        ];
        $sql = 'INSERT INTO ' . POSTS_TABLE . ' ' .$this->db->sql_build_array('INSERT', $sql_array) ;
        if(!$this->db->sql_query($sql)) {
            throw new \reaper\memberapp\exception\base($this->user->lang('EXCEPTION_POST_SQL'));
        } else {           
            return;
        }
    }

    public function check_required($post) {
        $required_array = [
            'flname', 'gamename', 'age', 'gender','found'
        ];
        
        foreach($post as $key => $val) {
            if(in_array($key, $required_array)) {
                if(empty($val)){
                    throw new \reaper\memberapp\exception\base($this->user->lang('EXCEPTION_FIELD_MISSING'));
                }
            }else {
                continue;
            }
        }
        return;
    }

    

} 