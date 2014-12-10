<?php

require_once __DIR__ . '/EntityTermLookupForProfiling.php';
require_once __DIR__ . '/TermSqlIndexForProfiling.php';

class WBProfiler {

    public function __construct() {
        $this->setupProfiling();
    }

    private function setupProfiling() {
        global $wgProfiler;

        $profiler = Profiler::instance();

        if ( $profiler instanceof ProfilerStub ) {
            $profiler = new ProfilerStandard( array( 'sampling' => 1, 'output' => 'ProfilerOutputText') + $wgProfiler );
            $profiler->setTemplated( true );
            Profiler::replaceStubInstance( $profiler );
        }
    }

    private function getTermLookup() {
        $stringNormalizer = new Wikibase\StringNormalizer();
        $termIndex = new Wikibase\TermSqlIndexForProfiling( $stringNormalizer );

        return new Wikibase\Lib\Store\EntityTermLookupForProfiling(
            $termIndex,
            \Wikibase\Repo\WikibaseRepo::getDefaultInstance()->getEntityLookup()
        );
    }

    public function getLabel_typeFilteredViaPhp( $itemId ) {
        if ( is_string( $itemId ) ) {
            $itemId = new Wikibase\DataModel\Entity\ItemId( $itemId );
        }

        $termLookup = $this->getTermLookup();

        try {
            $termLookup->getLabel_typeFilteredViaPhp( $itemId, 'en' );
        } catch ( Exception $ex ) {
            // ignore
        }
    }

    public function getLabel_typeFilteredViaDB( $itemId ) {
        if ( is_string( $itemId ) ) {
            $itemId = new Wikibase\DataModel\Entity\ItemId( $itemId );
        }

        $termLookup = $this->getTermLookup();

        try {
            $termLookup->getLabel_typeFilteredViaDB( $itemId, 'en' );
        } catch ( Exception $ex ) {
            // ignore
        }
    }

    public function output() {
        $out = Profiler::instance()->getOutput();
        var_export( $out );
    }

    public static function profile( $max ) {
        $instance = new self();
        $numericIds = range( 1, (int)$max );

        foreach ( $numericIds as $numericId ) {
            $itemId = Wikibase\DataModel\Entity\ItemId::newFromNumber( mt_rand( 1, (int)10000000 ) );

            if ( $numericId % 2 ) {
                $instance->getLabel_typeFilteredViaDB( $itemId );
                $instance->getLabel_typeFilteredViaPhp( $itemId );
            } else {
                $instance->getLabel_typeFilteredViaPhp( $itemId );
                $instance->getLabel_typeFilteredViaDB( $itemId );
            }

            wfWaitForSlaves();
        }

        $instance->output();
    }

}
