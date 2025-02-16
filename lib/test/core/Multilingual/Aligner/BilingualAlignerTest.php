<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/**
 * @group unit
 *
 */

class Multilingual_Aligner_BilingualAlignerTest extends TikiTestCase
{
    public $aligner;
    public function disabledTestReminder()
    {
        $this->fail("remember to reactivate all tests in BilingualAlignerTest");
    }


    ////////////////////////////////////////////////////////////////
    // Documentation tests
    //    These tests illustrate how to use this class.
    ////////////////////////////////////////////////////////////////

    public function thisIsHowYouCreateAbilingualAligner()
    {
        $aligner = new Multilingual_Aligner_BilingualAligner();
    }

    ////////////////////////////////////////////////////////////////
    // Note: In the rest of these tests, you can assume that
    //       $this->aligner is an instance of BilingualAligner
    //       created as above.
    ////////////////////////////////////////////////////////////////

    protected function setUp(): void
    {
        $this->aligner = new Multilingual_Aligner_BilingualAligner();
    }

    public function thisIsHowYouAlignTwoTexts()
    {
        $aligner = new Multilingual_Aligner_BilingualAligner();
        $en_entences = ["Hello earthlings. Take me to your leader."];
        $fr_sentences = ["Bonjour terriens. Inutile de résister. Amenez moi à votre chef."];
        $aligned_sentences = $aligner->align($en_entences, $fr_sentences);
        $first_pair = $aligned_sentences[0];
        $first_en_sent = $first_pair[0];
        $first_fr_sent = $first_pair[1];
    }

    ////////////////////////////////////////////////////////////////
    // Internal tests
    //    These tests check the internal workings of the class.
    ////////////////////////////////////////////////////////////////

    /**
     * @group multilingual
     */
    public function testSegmentIntoSentences()
    {
        $text = "This is sentence 1! This is sentence 2\n* This is sentence 3";
        $got_sentences = $this->aligner->segment_into_sentences($text);
        $exp_sentences = [
                                "This is sentence 1!",
                                " This is sentence 2\n",
                                "* This is sentence 3"
        ];
        $this->assertEquals(
            $exp_sentences,
            $got_sentences,
            "Sentences were not properly segmented"
        );
    }

    /**
     * @group multilingual
     */
    public function testSegmentParallelTextsToSentences()
    {
        $l1_text = "This is sentence 1! This is sentence 2.";
        $l2_text = "Voici la phrase 1! Voici la phrase 2.";

        $exp_l1_sentences = ["This is sentence 1!", " This is sentence 2."];
        $exp_l2_sentences = ["Voici la phrase 1!", " Voici la phrase 2."];

        $this->aligner->segment_parallel_texts_to_sentences($l1_text, $l2_text);

        $this->assertEquals(
            $exp_l1_sentences,
            $this->aligner->l1_sentences,
            "L1 sentences not generated properly."
        );

        $this->assertEquals(
            $exp_l2_sentences,
            $this->aligner->l2_sentences,
            "L2 sentences not generated properly."
        );
    }

    /**
     * @group multilingual
     */
    public function testSentenceLengthDelta()
    {

        $l1_sentence = "Hello world.";
        $l2_sentence = "Bonjour le monde.";
        $this->assertSentenceLengthDeltaIs(
            $l1_sentence,
            $l2_sentence,
            0.417,
            "Bad delta for case with two non-empty sentences."
        );

        $l1_sentence = "";
        $l2_sentence = "Bonjour le monde.";
        $this->assertSentenceLengthDeltaIs(
            $l1_sentence,
            $l2_sentence,
            1,
            "Bad delta for case with only L1 sentence empty."
        );

        $l1_sentence = "Hello world.";
        $l2_sentence = "";
        $this->assertSentenceLengthDeltaIs(
            $l1_sentence,
            $l2_sentence,
            1,
            "Bad delta for case with only L2 sentence empty."
        );

        $l1_sentence = "";
        $l2_sentence = "";
        $this->assertSentenceLengthDeltaIs(
            $l1_sentence,
            $l2_sentence,
            0,
            "Bad delta for case with both sentences empty."
        );

        $l1_sentence = null;
        $l2_sentence = "Bonjour le monde.";
        $this->assertSentenceLengthDeltaIs(
            $l1_sentence,
            $l2_sentence,
            1,
            "Bad delta for case with only L1 sentence null."
        );

        $l1_sentence = "Hello world.";
        $l2_sentence = null;

        $this->assertSentenceLengthDeltaIs(
            $l1_sentence,
            $l2_sentence,
            1,
            "Bad delta for case with only L2 sentence null."
        );

        $l1_sentence = null;
        $l2_sentence = null;

        $this->assertSentenceLengthDeltaIs(
            $l1_sentence,
            $l2_sentence,
            0,
            "Bad delta for case with both sentences null."
        );
    }

    public function disabledTestGenerateShortestPathMatrix()
    {

        $this->fail("Expected distance matrix is missing some destinations after some changes we made. Fix it.");

        $this->setupSegmentedSentences();
        $this->aligner->generate_shortest_path_matrix();

        $exp_cost_matrix = [];

        $exp_cost_matrix["-1n0|-1n0"]["-1m1|-1m1"] = "match_cost";
        $exp_cost_matrix["-1n0|-1n0"]["-1m2|-1m1"] = "match_cost";
        $exp_cost_matrix["-1n0|-1n0"]["-1m1|-1m2"] = "match_cost";
        $exp_cost_matrix["-1n0|-1n0"]["-1m1|-1m0"] = "match_cost";
        $exp_cost_matrix["-1n0|-1n0"]["-1m0|-1m1"] = "match_cost";

        $exp_cost_matrix["-1m1|-1m1"]["0m1|0m1"] = "match_cost";
        $exp_cost_matrix["-1m1|-1m1"]["0m1|0m2"] = "match_cost";
        $exp_cost_matrix["-1m1|-1m1"]["0m1|0m0"] = "match_cost";
        $exp_cost_matrix["-1m1|-1m1"]["0m0|0m1"] = "match_cost";
        $exp_cost_matrix["-1m1|-1m1"]["END"] = "goto_end_cost";

        $exp_cost_matrix["-1m2|-1m1"]["END"] = "goto_end_cost";
        $exp_cost_matrix["-1m2|-1m1"]["1m0|0m1"] = "match_cost";
        $exp_cost_matrix["-1m2|-1m1"]["1m0|0m2"] = "match_cost";
        $exp_cost_matrix["-1m2|-1m1"]["1m0|0m0"] = "match_cost";

        $exp_cost_matrix["-1m1|-1m2"]["0m1|1m1"] = "match_cost";
        $exp_cost_matrix["-1m1|-1m2"]["END"] = "goto_end_cost";
        $exp_cost_matrix["-1m1|-1m2"]["0m1|1m0"] = "match_cost";
        $exp_cost_matrix["-1m1|-1m2"]["0m0|1m1"] = "match_cost";

        $exp_cost_matrix["-1m1|-1m0"]["0m1|-1m1"] = "match_cost";
        $exp_cost_matrix["-1m1|-1m0"]["END"] = "goto_end_cost";
        $exp_cost_matrix["-1m1|-1m0"]["0m1|-1m2"] = "match_cost";
        $exp_cost_matrix["-1m1|-1m0"]["0m1|-1m0"] = "match_cost";
        $exp_cost_matrix["-1m1|-1m0"]["0m0|-1m1"] = "match_cost";

        $exp_cost_matrix["-1m0|-1m1"]["-1m1|0m1"] = "match_cost";
        $exp_cost_matrix["-1m0|-1m1"]["-1m1|0m2"] = "match_cost";
        $exp_cost_matrix["-1m0|-1m1"]["-1m2|0m1"] = "match_cost";
        $exp_cost_matrix["-1m0|-1m1"]["-1m1|0m0"] = "match_cost";
        $exp_cost_matrix["-1m0|-1m1"]["-1m0|0m1"] = "match_cost";

        $exp_cost_matrix["0m1|0m1"]["1m0|1m1"] = "match_cost";
        $exp_cost_matrix["0m1|0m1"]["1m0|1m0"] = "match_cost";
        $exp_cost_matrix["0m1|0m1"]["END"] = "goto_end_cost";

        $exp_cost_matrix["0m1|0m2"]["1m0|2m0"] = "match_cost";
        $exp_cost_matrix["0m1|0m2"]["END"] = "goto_end_cost";

        $exp_cost_matrix["0m1|0m0"]["1m0|0m1"] = "match_cost";
        $exp_cost_matrix["0m1|0m0"]["1m0|0m2"] = "match_cost";
        $exp_cost_matrix["0m1|0m0"]["1m0|0m0"] = "match_cost";
        $exp_cost_matrix["0m1|0m0"]["END"] = "goto_end_cost";

        $exp_cost_matrix["0m0|0m1"]["0m1|1m1"] = "match_cost";
        $exp_cost_matrix["0m0|0m1"]["0m1|1m0"] = "match_cost";
        $exp_cost_matrix["0m0|0m1"]["0m0|1m1"] = "match_cost";
        $exp_cost_matrix["0m0|0m1"]["END"] = "goto_end_cost";

        $exp_cost_matrix["1m0|0m1"]["1m0|1m1"] = "match_cost";
        $exp_cost_matrix["1m0|0m1"]["END"] = "goto_end_cost";

        $exp_cost_matrix["0m1|1m1"]["END"] = "goto_end_cost";

        $exp_cost_matrix["0m1|1m0"]["1m0|1m1"] = "match_cost";
        $exp_cost_matrix["0m1|1m0"]["END"] = "goto_end_cost";

        $exp_cost_matrix["0m0|1m1"]["0m1|2m0"] = "match_cost";
        $exp_cost_matrix["0m0|1m1"]["END"] = "goto_end_cost";

        $exp_cost_matrix["0m1|-1m1"]["1m0|0m1"] = "match_cost";
        $exp_cost_matrix["0m1|-1m1"]["END"] = "goto_end_cost";

        $exp_cost_matrix["0m1|-1m2"]["1m0|1m1"] = "match_cost";
        $exp_cost_matrix["0m1|-1m2"]["END"] = "goto_end_cost";

        $exp_cost_matrix["0m1|-1m0"]["1m0|-1m1"] = "match_cost";
        $exp_cost_matrix["0m1|-1m0"]["END"] = "goto_end_cost";

        $exp_cost_matrix["0m0|-1m1"]["0m1|0m1"] = "match_cost";
        $exp_cost_matrix["0m0|-1m1"]["0m1|0m2"] = "match_cost";
        $exp_cost_matrix["0m0|-1m1"]["0m1|0m0"] = "match_cost";
        $exp_cost_matrix["0m0|-1m1"]["0m0|0m1"] = "match_cost";
        $exp_cost_matrix["0m0|-1m1"]["END"] = "goto_end_cost";

        $exp_cost_matrix["-1m1|0m1"]["0m1|1m1"] = "match_cost";
        $exp_cost_matrix["-1m1|0m1"]["0m1|1m0"] = "match_cost";
        $exp_cost_matrix["-1m1|0m1"]["0m0|1m1"] = "match_cost";
        $exp_cost_matrix["-1m1|0m1"]["END"] = "goto_end_cost";

        $exp_cost_matrix["-1m1|0m2"]["0m1|2m0"] = "match_cost";
        $exp_cost_matrix["-1m1|0m2"]["END"] = "goto_end_cost";

        $exp_cost_matrix["-1m2|0m1"]["1m0|1m1"] = "match_cost";
        $exp_cost_matrix["-1m2|0m1"]["END"] = "goto_end_cost";

        $exp_cost_matrix["-1m1|0m0"]["0m1|0m1"] = "match_cost";
        $exp_cost_matrix["-1m1|0m0"]["0m1|0m2"] = "match_cost";
        $exp_cost_matrix["-1m1|0m0"]["0m1|0m0"] = "match_cost";
        $exp_cost_matrix["-1m1|0m0"]["0m0|0m1"] = "match_cost";
        $exp_cost_matrix["-1m1|0m0"]["END"] = "goto_end_cost";

        $exp_cost_matrix["-1m0|0m1"]["-1m1|1m1"] = "match_cost";
        $exp_cost_matrix["-1m0|0m1"]["-1m2|1m1"] = "match_cost";
        $exp_cost_matrix["-1m0|0m1"]["-1m1|1m0"] = "match_cost";
        $exp_cost_matrix["-1m0|0m1"]["-1m0|1m1"] = "match_cost";
        $exp_cost_matrix["-1m0|0m1"]["END"] = "goto_end_cost";

        $exp_cost_matrix["1m0|1m1"]["END"] = "goto_end_cost";

        $exp_cost_matrix["0m1|2m0"]["END"] = "goto_end_cost";

        $exp_cost_matrix["1m0|-1m1"]["1m0|0m1"] = "match_cost";
        $exp_cost_matrix["1m0|-1m1"]["END"] = "goto_end_cost";

        $exp_cost_matrix["-1m1|1m1"]["0m1|2m0"] = "match_cost";
        $exp_cost_matrix["-1m1|1m1"]["END"] = "goto_end_cost";

        $exp_cost_matrix["-1m2|1m1"]["END"] = "goto_end_cost";

        $exp_cost_matrix["-1m1|1m0"]["0m1|1m1"] = "match_cost";
        $exp_cost_matrix["-1m1|1m0"]["0m1|1m0"] = "match_cost";
        $exp_cost_matrix["-1m1|1m0"]["0m0|1m1"] = "match_cost";
        $exp_cost_matrix["-1m1|1m0"]["END"] = "goto_end_cost";

        $exp_cost_matrix["-1m0|1m1"]["-1m1|2m0"] = "match_cost";
        $exp_cost_matrix["-1m0|1m1"]["END"] = "goto_end_cost";

        $exp_cost_matrix["-1m1|2m0"]["0m1|2m0"] = "match_cost";

        $exp_cost_matrix["-1m1|2m0"]["END"] = "goto_end_cost";

        $exp_cost_matrix["-1m0|2m0"]["END"] = "goto_end_cost";

        $exp_cost_matrix["-1m2|2m0"]["END"] = "goto_end_cost";

        $exp_cost_matrix["0m0|2m0"]["END"] = "goto_end_cost";

        $exp_cost_matrix["1m0|-1m0"]["END"] = "goto_end_cost";

        $exp_cost_matrix["1m0|-1m2"]["END"] = "goto_end_cost";

        $exp_cost_matrix["1m0|0m0"]["END"] = "goto_end_cost";

        $exp_cost_matrix["1m0|0m1"]["END"] = "goto_end_cost";

        $exp_cost_matrix["1m0|0m2"]["END"] = "goto_end_cost";

        $exp_cost_matrix["1m0|1m0"]["END"] = "goto_end_cost";

        $exp_cost_matrix["1m0|2m0"]["END"] = "goto_end_cost";

        $this->assertCostMatrixEquals(
            $exp_cost_matrix,
            $this->aligner->cost_matrix,
            "Cost matrix was wrong."
        );
    }

    /**
     * @group multilingual
     */
    public function testParseNodeID()
    {
        $this->assertParseNodeIDYields(
            '3m1|5m1',
            [3, 'm', 1, 5, 'm', 1],
            "Parsed node ID info was wrong for case where sentences are matched."
        );

        $this->assertParseNodeIDYields(
            '3m1|5m0',
            [3, 'm', 1, 5, 'm', 0],
            "Parsed node ID info was wrong for case where sentences were skipped."
        );

        $this->assertParseNodeIDYields(
            '-1m1|-1m1',
            [-1, 'm', 1, -1, 'm', 1],
            "Parsed node ID info was wrong for case with sentence number = -1 (i.e., cursor before first sentences on both sides)."
        );

        $this->assertParseNodeIDYields(
            '-1n0|-1n0',
            [-1, 'n', 0, -1, 'n', 0],
            "Parsed node ID info was wrong for START node '-1n0|-1n0'."
        );
    }


    /**
     * @group multilingual
     */
    public function testGenerateNodeID()
    {
        $this->setupSegmentedSentences();

        $this->assertEquals(
            '0m1|0m0',
            $this->aligner->generate_node_ID(0, 'm', 1, 0, 'm', 0)
        );

        $this->assertEquals(
            '1m0|1m1',
            $this->aligner->generate_node_ID(1, 'm', 2, 1, 'm', 1),
            "Node ID should never go passed the last L1 or L2 sentence number"
        );

        $this->assertEquals(
            '-1n0|-1n0',
            $this->aligner->generate_node_ID(-1, 'n', 0, -1, 'n', 0),
            "Node ID was wrong for START node '-1n0|-1n0'."
        );
    }

    /**
     * @group multilingual
     */
    public function testSentencesAtThisNode()
    {
        $this->assertSentencesAtThisNode(
            '3m1|5m1',
            [4, 6],
            "Current sentences were wrong for node with matches on both sides."
        );

        $this->assertSentencesAtThisNode(
            '-1m1|-1m1',
            [0, 0],
            "Current sentences were wrong for initial nodes (i.e., sentence number = -1)"
        );

        $this->assertSentencesAtThisNode(
            '4m1|5m0',
            [5, 5],
            "Current sentences were wrong for case where we skip a sentence."
        );

        $this->assertSentencesAtThisNode(
            '-1n0|-1n0',
            [-1, -1],
            "Current sentences were wrong for START node '-1n0|-1n0'."
        );
    }

    /**
     * @group multilingual
     */
    public function testSentencesPrecedingThisNode()
    {
        $node = '3m1|5m1';
        $sentences_preceding_node = $this->aligner->sentences_preceding_this_node($node);
        $this->assertEquals(
            [3, 5],
            $sentences_preceding_node,
            "Sentences preceding node '$node' were wrong."
        );
    }

    /**
     * @group multilingual
     */
    public function testComputeNodeTransitionCost()
    {
        $this->setupSegmentedSentences();

        $this->assertComputeNodeTransitionCostYields(
            "0m1|0m1",
            0,
            "Transition cost failed for 1 to 1 match"
        );

        $this->assertComputeNodeTransitionCostYields(
            "0m1|0m2",
            1.29,
            "Transition cost failed for 1 to 2 match"
        );

        $this->assertComputeNodeTransitionCostYields(
            "0m2|0m1",
            0.58,
            "Transition cost failed for 2 to 1 match"
        );

        $this->assertComputeNodeTransitionCostYields(
            "0m1|0m0",
            1,
            "Transition cost failed for L1 side skip"
        );

        $this->assertComputeNodeTransitionCostYields(
            "0m0|0m1",
            1,
            "Transition cost failed for L2 side skip"
        );
    }

    ////////////////////////////////////////////////////////////////
    // Helper methods
    ////////////////////////////////////////////////////////////////

    private function assertSentenceLengthDeltaIs($l1_sentence, $l2_sentence, $exp_delta, $message)
    {
        $got_delta = $this->aligner->sentence_length_delta($l1_sentence, $l2_sentence);
        $message = $message . "\nSentence length delta was wrong.";
        $this->assertEqualsWithDelta($exp_delta, $got_delta, 0.001, $message);
    }

    private function setupSegmentedSentences()
    {
        $en_entences = "Hello earthlings. Take me to your leader.";
        $fr_sentences = "Bonjour terriens. Inutile de résister. Amenez moi à votre chef.";
        $this->aligner->segment_parallel_texts_to_sentences($en_entences, $fr_sentences);
    }

    private function assertParseNodeIDYields($node_id, $exp_parsed_info, $message)
    {
        //       print "-- assert_parse_node_ID_yields: \$node_id=$node_id\n";
        $parsed_info = $this->aligner->parse_node_ID($node_id);
        $this->assertEquals($exp_parsed_info, $parsed_info, "$message\nParsed info was wrong for node ID: '$node_id'");
    }

    private function assertSentencesAtThisNode($node_id, $exp_next_sentences, $message)
    {
        $next_sentences = $this->aligner->sentences_at_this_node($node_id);
        $this->assertEquals(
            $exp_next_sentences,
            $next_sentences,
            $message . "\nNext sentences were wrong for node '$node_id'"
        );
    }

    private function assertCostMatrixEquals($exp_cost_matrix, $got_cost_matrix, $message)
    {

        //     print "-- assertCostMatrixEquals: \$exp_cost_matrix=\n";var_dump($exp_cost_matrix);print"\n";
        //     print "-- assertCostMatrixEquals: \$got_cost_matrix=\n";var_dump($got_cost_matrix);print"\n";

        $exp_origins = array_keys($exp_cost_matrix);
        sort($exp_origins);
        $got_origins = array_keys($got_cost_matrix);
        sort($got_origins);

        //      print "-- assertCostMatrixEquals: \$exp_origins=\n";var_dump($exp_origins);print"\n";
        //      print "-- assertCostMatrixEquals: \$got_origins=\n";var_dump($got_origins);print"\n";

        $this->assertEquals(
            $exp_origins,
            $got_origins,
            "List of origins in cost matrix differed."
        );

        foreach (array_keys($exp_cost_matrix) as $origin) {
            //         print "-- assertCostMatrixEquals: \$exp_cost_matrix[$origin]=";var_dump($exp_cost_matrix[$origin]);print"\n";
            //         print "-- assertCostMatrixEquals: \$got_cost_matrix[$origin]=";var_dump($got_cost_matrix[$origin]);print"\n";
            $this->assertEquals(
                $exp_cost_matrix[$origin],
                $got_cost_matrix[$origin],
                "Costs from origin $origin differed"
            );
        }
    }

    public function assertComputeNodeTransitionCostYields(
        $destination_node,
        $exp_cost,
        $message
    ) {

        $got_cost = $this->aligner->compute_node_transition_cost($destination_node);
        $tolerance = 0.01;
        $this->assertEqualsWithDelta(
            $exp_cost,
            $got_cost,
            $tolerance,
            $message . "\nTransition cost to node '$destination_node' was wrong"
        );
    }
}
