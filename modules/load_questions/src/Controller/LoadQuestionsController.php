<?php

namespace Drupal\load_questions\Controller;

use Drupal\node\Entity\Node;
use Drupal\Core\Controller\ControllerBase;
use Drupal\paragraphs\Entity\Paragraph;

class LoadQuestionsController extends ControllerBase
{
    public function load($questionid, $questiontype)
    {
        $connection = \Drupal::database();

        $options = array();

        if ($questiontype == 0) {
            // Create an object of type Select
            $query1 = $connection->select('lt_multiple_choice_questions', 'm');

            // Add extra detail to this query object: a condition, fields and a range
            $query1->condition('m.ID_GLOBAL', $questionid)
                ->fields('m', ['QUESTION']);
            $result1 = $query1->execute();
            $question = $result1->fetchField();

            $query2 = $connection->select('lt_multiple_choice_questions', 'm');
            $query2->condition('m.ID_GLOBAL', $questionid)
                ->fields('m', ['OPTION0']);
            $result2 = $query2->execute();
            $option1 = $result2->fetchField();

            $query3 = $connection->select('lt_multiple_choice_questions', 'm');
            $query3->condition('m.ID_GLOBAL', $questionid)
                ->fields('m', ['OPTION1']);
            $result3 = $query3->execute();
            $option2 = $result3->fetchField();

            $query4 = $connection->select('lt_multiple_choice_questions', 'm');
            $query4->condition('m.ID_GLOBAL', $questionid)
                ->fields('m', ['OPTION2']);
            $result4 = $query4->execute();
            $option3 = $result4->fetchField();

            $query5 = $connection->select('lt_multiple_choice_questions', 'm');
            $query5->condition('m.ID_GLOBAL', $questionid)
                ->fields('m', ['OPTION3']);
            $result5 = $query5->execute();
            $option4 = $result5->fetchField();

            $query6 = $connection->select('lt_multiple_choice_questions', 'm');
            $query6->condition('m.ID_GLOBAL', $questionid)
                ->fields('m', ['OPTION4']);
            $result6 = $query6->execute();
            $option5 = $result6->fetchField();

            $query7 = $connection->select('lt_multiple_choice_questions', 'm');
            $query7->condition('m.ID_GLOBAL', $questionid)
                ->fields('m', ['OPTION5']);
            $result7 = $query7->execute();
            $option6 = $result7->fetchField();

            $query8 = $connection->select('lt_multiple_choice_questions', 'm');
            $query8->condition('m.ID_GLOBAL', $questionid)
                ->fields('m', ['OPTION6']);
            $result8 = $query8->execute();
            $option7 = $result8->fetchField();

            $query9 = $connection->select('lt_multiple_choice_questions', 'm');
            $query9->condition('m.ID_GLOBAL', $questionid)
                ->fields('m', ['OPTION7']);
            $result9 = $query9->execute();
            $option8 = $result9->fetchField();

            $query10 = $connection->select('lt_multiple_choice_questions', 'm');
            $query10->condition('m.ID_GLOBAL', $questionid)
                ->fields('m', ['OPTION8']);
            $result10 = $query10->execute();
            $option9 = $result10->fetchField();

            $query11 = $connection->select('lt_multiple_choice_questions', 'm');
            $query11->condition('m.ID_GLOBAL', $questionid)
                ->fields('m', ['OPTION9']);
            $result11 = $query11->execute();
            $option10 = $result11->fetchField();

            array_push($options,$option1,$option2,$option3,$option4,$option5,$option6,$option7,$option8,$option9,$option10);

            $query12 = $connection->select('lt_multiple_choice_questions', 'm');
            $query12->condition('m.ID_GLOBAL', $questionid)
                ->fields('m', ['IMAGE_PATH']);
            $result12 = $query12->execute();
            $image = $result12->fetchField();


            $data = file_get_contents('/var/www/html/images/' . $image);
            $file = file_save_data($data, 'public://' . $image, FILE_EXISTS_RENAME);


            $node = Node::create([
                'type' => 'question',
                'title' => 'Question multiple choice',
                'field_question' => $question,
                //'field_correct_answers_label' => '<strong>Correct Answer(s)</strong>',
                'field_question_image' => [
                    'target_id' => $file->id(),
                    'alt' => 'Drupal',
                    'title' => 'Question image'
                ],
            ]);
            $node->field_correct_answers_label = array(
                array(
                    "value"  =>  '<strong>Correct Answer(s)</strong>',
                    "format" => "full_html"
                )
            );

            for ($i = 1; $i <= 10; $i++) {
                $paragraph = Paragraph::create(['type' => 'answers',]);
                $paragraph->set('field_answer', $options[$i]);
                $paragraph->isNew();
                $paragraph->save();

                // Grab any existing paragraphs from the node, and add this one
                $current = $node->get('field_correct_answer_s_')->getValue();
                $current[] = array(
                    'target_id' => $paragraph->id(),
                    'target_revision_id' => $paragraph->getRevisionId(),
                );
                $node->set('field_correct_answer_s_', $current);
            }

            assert($node->isNew(), TRUE);
            $node->save();
            assert($node->isNew(), FALSE);
            return array(
                '#title' => $question,
                '#markup' => $option1,
            );
        }
    }

}
