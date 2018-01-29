<!DOCTYPE HTML>  
<html>
<head>
<style>
   #centro {
    width:100px;
    height:100px;
    position:absolute;
    top:50%;
    left:50%;
    margin-top:-50px;
    margin-left:-50px;
}

</style>
</head>
<body>
<img src="img/load.gif" id="centro" />

<?php

require('../config.php');
//require('/home/eadiepre/public_html/lib/moodlelib.php');
//require_once($CFG->libdir.'/authlib.php');
require_once(__DIR__ . '/lib.php');

//echo "Carregando...";
$emailC = $_POST['email'];
$nome = $_POST['name'];
$snome = $_POST['lName'];
$senha = $_POST['pwd'];
$curso = $_POST['course'];

//echo "</br> Usuario: " . $nome;
//echo "</br> Usuario: " . $snome;
//echo "</br> curso: " . $curso;
//echo "</br> Email: " . $emailC;
//echo "</br> senha: " . $senha;
//echo "</br> course: " . $curso;
global $DB;

//Atenção pelo prefixo da tabela
$user = $DB->get_record_sql('SELECT * FROM mdl_user WHERE email= ?', array($emailC));
//echo "</br> Usuario: " . $user->id;
//Verifica se usuario existe 
if (empty($user->username)) {
    cadastrar($emailC, $nome, $snome, $senha);
} else {

    verificarCursos($user);

}

//Redirecionamento 
//echo "<meta HTTP-EQUIV='Refresh' CONTENT='0;URL=(sua url)'>";

function cadastrar($emailC, $nome, $snome, $senha) {
    
        //echo "</br> entrou no cadastrar: ";
        //Recebe nome e sobrenome separado

        if (empty($snome)) {
            $separanome = subName($nome);
            $primeironome = $separanome['name'];
            $sobrenome = $separanome['subname'];
            
        } else {
            
            $primeironome = $nome;
            $sobrenome = $snome;
        }
        
        $username = $emailC;
        $passwordusuario = $senha;
    
    
        if (empty($primeironome)) {
            echo "Nome é um parâmetro obrigatório";
            exit;
        }
        if (empty($sobrenome)) {
            echo "Sobrenome é um parâmetro obrigatório";
            exit;
        }
        if (empty($emailC)) {
            echo "E-mail é um parâmetro obrigatório";
            exit;
        }
        if (empty($username)) {
            echo "Login é um parâmetro obrigatório";
            exit;
        }
    
        //Criar Novo Usuario
        $newuser = new object();
        $newuser->id = '';
        $newuser->username = $username;
        $newuser->firstname = $primeironome;
        $newuser->lastname = $sobrenome;
        $newuser->email = $emailC;
        $newuser->auth = 'manual';
        $newuser->password = crypt($passwordusuario);
        $newuser->mnethostid = 1;
        $newuser->confirmed = 1;
        $newuser->description = '';
        $newuser->lang = 'pt_br';
        $newuser->theme = '';
        $newuser->timezone = 99;
        $newuser->firstaccess = 0;
        $newuser->lastaccess = 0;
        $newuser->lastlogin = 0;
        $newuser->currentlogin = '';
        $newuser->lastip = '';
        $newuser->secret = '';
        $newuser->picture = 0;
        $newuser->url = '';
        $newuser->description = '';
        $newuser->mailformat = 1;
        $newuser->maildigest = 0;
        $newuser->maildisplay = 2;
        $newuser->htmleditor = 1;
        $newuser->ajax = 1;
        $newuser->autosubscribe = 1;
        $newuser->trackforums = 0;
        $newuser->timemodified = '';
        $newuser->trustbitmask = '';
        $newuser->imagealt = '';
        $newuser->screenreader = 0;
    
        //echo "</br> chegou no salvar: ";
        $newuser->id = save($newuser);
        verificarCursos($newuser);
    
    
        //matricular($newuser);
        complete_user_login($newuser);
    
        //Redirecionamento 
        //echo "<meta HTTP-EQUIV='Refresh' CONTENT='0;URL=http://endereço'>";
    }
    function subName($name) {
        
            $pos = strpos($name, ' ');
        
            $ret = array();
        
            if ($pos !== FALSE) {
        
                $ret['name'] = substr($name, 0, $pos);
        
                $ret['subname'] = substr($name, $pos + 1, strlen($name));
            }
        
            return $ret;
    }

    function verificarCursos($user) {
        
        
            $curso = $_POST['course'];
            $categoria = $_POST['categoria'];
            $categoria = 0;
            
             if (empty($curso)&& empty($categoria)) {
                echo "Não foi passado nenhum curso ou categoria de parametro para a matricula.";
                exit;
            }
            
           //echo "</br> Curso: " . $curso;
        
        
            if ($categoria==0) {
                cadastrarCurso($curso, $user);
            } else {
                cadastrarCategoria($categoria, $user);
            }
            complete_user_login($user);
        }
        
        function cadastrarCurso($curso, $user) {
        
            global $DB;
           
            $userid = $user->id;
        
            if (matriculaExistente($curso, $userid) == FALSE) {
                $queryChave = "SELECT id FROM mdl_enrol WHERE courseid=$curso AND enrol='manual'";
                $cursoRecord = $DB->get_record_sql($queryChave);
                $roleid = 5;
                //echo "</br> Chegou aqui: ";
                enroll_to_course($curso, $userid, $roleid, 3, 0);
            }
        }
        
        function matriculaExistente($curso, $userid) {
        
            global $DB;
            //echo "</br> Verificar curso: ";
            $query = "SELECT c.id FROM mdlwn_role_assignments rs INNER JOIN mdlwn_context e ON rs.contextid=e.id INNER JOIN mdlwn_course c ON c.id = e.instanceid WHERE e.contextlevel=50 AND rs.userid=$userid";
            $usrCursos = $DB->get_records_sql($query);
            foreach ($usrCursos as $cursoU) {
                if ($cursoU->id == $curso) {
                    //echo "</br> Aluno já esta matriculado no curso: " . $cursoU->id;
                    return TRUE;
                }
            }
            return FALSE;
        }
        
        function cadastrarCategoria($categoria, $user) {
        
            global $DB;
        
            $query = "SELECT * FROM `mdl_course` WHERE category = $categoria";
            $usrCursos = $DB->get_records_sql($query);
            foreach ($usrCursos as $curso) {
                cadastrarCuro($curso->id, $user);
            }
            
        }
        
        
        
        function save($dto) {
            global $DB;
            return $DB->insert_record('user', $dto);
        }
        
        function enroll_to_course($courseid, $userid, $roleid, $extendbase, $extendperiod) {
            global $DB;
        
            $instance = $DB->get_record('enrol', array('courseid' => $courseid, 'enrol' => 'manual'), '*', MUST_EXIST);
            $course = $DB->get_record('course', array('id' => $instance->courseid), '*', MUST_EXIST);
            $today = time();
            $today = make_timestamp(date('Y', $today), date('m', $today), date('d', $today), 0, 0, 0);
            if (!$enrol_manual = enrol_get_plugin('manual')) {
                throw new coding_exception('Can not instantiate enrol_manual');
            }
            switch ($extendbase) {
                case 2:
                    $timestart = $course->startdate;
                    break;
                case 3:
                default:
                    $timestart = $today;
                    break;
            }
            if ($extendperiod <= 0) {
                $timeend = 0;
            }   // extendperiod are seconds
            else {
                $timeend = $timestart + $extendperiod;
            }
            $enrolled = $enrol_manual->enrol_user($instance, $userid, $roleid, $timestart, $timeend);
        }
?>
</body>
</html>