<?php

namespace Drupal\load_questions\Controller;
use Drupal\node\Entity\Node;

class LoadQuestionsController
{
    public function load()
    {
        $connection = \Drupal::database();

        // Create an object of type Select
        $query = $connection->select('lt_multiple_choice_questions', 'm');

        // Add extra detail to this query object: a condition, fields and a range
        $query->condition('m.ID', 1)
            ->fields('m', ['QUESTION']);

        $result = $query->execute();
        $question = "";
        //foreach ($result as $record) {
            $question = $result->fetchField();
        //}



        $data = file_get_contents('https://www.drupal.org/files/druplicon-small.png');
        $file = file_save_data($data, 'public://druplicon.png', FILE_EXISTS_RENAME);

        $node = Node::create([
            'type'        => 'question_multiple_choice',
            'title'       => 'Question multiple choice',
            'field_question_text' => $question,
            'field_qmc_option1' => 'blanc',
            'field_qmc_option2' => 'noir',
            'field_qmc_option3' => 'gris',
            'field_qmc_option4' => 'bleu',
            'field_qmc_option5' => 'vert',
        ]);
        assert($node->isNew(), TRUE);
        $node->save();
        assert($node->isNew(), FALSE);

        return array(
            '#title' => 'Hello World!',
            '#markup' => 'Here is a new question for you.',
        );
    }

}
