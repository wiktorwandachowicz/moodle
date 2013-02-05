<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Local language pack from http://proba.edu.p.lodz.pl
 *
 * @package    mod
 * @subpackage data
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['actionname'] = 'Nazwa akcji:';
$string['actionnameexists'] = 'Akcja o takiej nazwie dla wybranego stanu startowego już istnieje ({$a})';
$string['actions'] = 'Akcje:';
$string['addaction'] = 'Dodaj akcję';
$string['allowstateforrole'] = 'Zezwól użytkownikom w roli {$a->fromrole} na wykonywanie akcji przepływu pracy w stanie {$a->state}';
$string['availableactions'] = '[Akcje] : Stan startowy &gt;&gt; Stan docelowy';
$string['configallowstateforrole'] = 'Zezwalaj użytkownikom w rolach na górze na wykonywanie akcji przepływu pracy w określonych stanach dla wpisów w bazie danych';
$string['createstate'] = 'Utwórz stan';
$string['createworkflow'] = 'Utwórz przepływ pracy';
$string['currentcourse'] = 'Aktualny kurs';
$string['data:manageworkflows'] = 'Zarządzaj przepływami pracy';
$string['data:superviseworkflow'] = 'Otrzymuj powiadomienia o zmianach stanu przepływu pracy dla wpisów bazy danych';
$string['data:useworkflow'] = 'Zmieniaj stan przepływu pracy dla wpisów bazy danych';
$string['deleteaction'] = 'Usuń akcję';
$string['deleteactionconfirm'] = 'Czy na pewno usunąć tę akcję? {$a}';
$string['deleteselectedaction'] = 'Usuń wybraną akcję';
$string['deleteselectedstate'] = 'Usuń wybrany stan';
$string['deleteselectedworkflow'] = 'Usuń wybrany przepływ pracy';
$string['deletestate'] = 'Usuń stan';
$string['deletestateconfirm'] = 'Czy na pewno usunąć ten stan? {$a}';
$string['deleteworkflow'] = 'Usuń przepływ pracy';
$string['deleteworkflowconfirm'] = 'Masz całkowitą pewność, że chcesz usunąć ten przepływ pracy? {$a}';
$string['editaction'] = 'Edytuj akcję';
$string['editstate'] = 'Edytuj stan';
$string['editworkflow'] = 'Edytuj przepływ pracy';
$string['erroreditaction'] = 'Błąd podczas edycji akcji przepływu pracy';
$string['erroreditstate'] = 'Błąd podczas edycji stanu przepływu pracy';
$string['erroreditworkflow'] = 'Błąd podczas edycji przepływu pracy';
$string['incorrectstate'] = 'Niewłaściwy stan!';
$string['initstate'] = 'Stan początkowy';
$string['localworkflow'] = 'Przepływ pracy dla kursu';
$string['localworkflow_help'] = 'Określa czy przepływ pracy jest widoczny tylko dla bieżącego kursu, lub czy może być używany we wszystkich kursach. Tylko administrator może zmienić to ustawienie.';
$string['nodelete'] = 'Usunięcie tego wpisu jest niedozwolone';
$string['noedit'] = 'Edycja tego wpisu jest niedozwolona';
$string['nostatechange'] = 'Zmiana stanu tego wpisu jest niedozwolona';
$string['nostates'] = '-- brak zdefiniowanych stanów --';
$string['notifyboth'] = 'Powiadamianie TWÓRCY oraz NADZORCY';
$string['notifycreator'] = 'Powiadamianie TWÓRCY';
$string['notifynobody'] = 'NIE POWIADAMIAJ nikogo';
$string['notifysupervisor'] = 'Powiadamiaj NADZORCĘ';
$string['selectedworkflow'] = 'Wybrany przepływ pracy:';
$string['selectstate'] = '-- wybierz stan --';
$string['selectworkflow'] = '-- wybierz przepływ pracy --';
$string['showtargetsforstate'] = 'Pokaż stany docelowe';
$string['showwfstates'] = 'Pokaż stany';
$string['startstate'] = 'Stan startowy';
$string['startstates'] = 'Stany startowe';
$string['state'] = 'Stan';
$string['statename'] = 'Nazwa stanu';
$string['statenameexists'] = 'Stan o takiej nazwie już istnieje ({$a})';
$string['statenotification'] = 'Powiadomienia przez e-mail';
$string['statenotificationemailbody'] = 'Użytkownik "{$a->user}" zmienił stan wpisu w "{$a->db}" na "{$a->state}"

{$a->url}';
$string['statenotificationemailsubject'] = '[{$a->course}] Zmiana w bazie danych {$a->db}';
$string['statenotification_help'] = 'Kto będzie powiadamiany przez wysłanie e-mail, kiedy wpis przechodzi do tego stanu. Domyślnie jest to TWÓRCA wpisu.';
$string['states'] = 'Stany';
$string['targetstate'] = 'Stan docelowy';
$string['wfactions'] = 'Akcje';
$string['wfallows'] = 'Dozwolone role dla stanów';
$string['wfdefinitions'] = 'Definicje';
$string['wfdefslist'] = 'Lista zdefiniowanych przepływów pracy';
$string['workflow'] = 'Przepływ pracy';
$string['workflowenable'] = 'Włącz przepływ pracy';
$string['workflowname'] = 'Nazwa przepływu pracy';
$string['workflownameexists'] = 'Przepływ pracy o takiej nazwie już istnieje ({$a})';
$string['workflows'] = 'Przepływy pracy';
$string['workflowstate'] = 'Stan przepływu pracy';
