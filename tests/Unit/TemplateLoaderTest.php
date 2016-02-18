<?php namespace Brain\Cortex\Tests\Unit;

use Brain\Cortex\Tests\TestCase;

class TemplateLoaderTest extends TestCase {

    private function get( $filters = [ ] ) {
        $hooks = \Mockery::mock( '\Brain\Cortex\Tests\MockedHooks' )->makePartial();
        if ( ! empty( $filters ) ) {
            foreach ( $filters as $tag => $return ) {
                $hooks->mock( $tag, $return );
            }
        }
        $loader = \Mockery::mock( '\Brain\Cortex\TemplateLoader' )->makePartial();
        $loader->shouldReceive( 'getHooks' )->withNoArgs()->andReturn( $hooks );
        return $loader;
    }

    function testLoadNullIfWrongHook() {
        \WP_Mock::wpFunction( 'did_action', [ 'return' => TRUE ] );
        $loader = $this->get();
        assertFalse( $loader->load( 'foo' ) );
    }

    function testLoadNullIfBadTemplate() {
        \WP_Mock::wpFunction( 'did_action', [ 'return' => FALSE ] );
        $loader = $this->get();
        assertFalse( $loader->load() );
    }

    function testLoad() {
        \WP_Mock::wpFunction( 'did_action', [ 'return' => FALSE ] );
        $loader = $this->get();
        $hooks = $loader->getHooks();
        $hooks->shouldReceive( 'addAction' )
            ->with( 'cortex.template_load', 'template_redirect', [ $loader, 'loadTemplate' ], 50 )
            ->once()
            ->andReturnSelf();
        assertTrue( $loader->load( 'foo' ) );
    }

    function testLoadTemplateFalseIfWrongHook() {
        \WP_Mock::wpFunction( 'current_filter', [ 'return' => 'foo' ] );
        $loader = $this->get();
        assertFalse( $loader->loadTemplate() );
    }

    function testLoadTemplateExitIFNotPreload() {
        \WP_Mock::wpFunction( 'current_filter', [ 'return' => 'template_redirect' ] );
        $loader = $this->get();
        $loader->shouldReceive( 'preLoad' )->once()->withNoArgs()->andReturnNull();
        assertFalse( $loader->loadTemplate() );
    }

    function testLoadTemplate() {
        \WP_Mock::wpFunction( 'current_filter', [ 'return' => 'template_redirect' ] );
        $loader = $this->get();
        $loader->shouldReceive( 'preLoad' )->once()->withNoArgs()->andReturn( TRUE );
        $loader->shouldReceive( 'getTemplate' )->once()->withNoArgs()->andReturn( 'foo' );
        $loader->shouldReceive( 'loadFile' )->once()->with( 'foo', TRUE )->andReturn( 'Loaded!' );
        assertEquals( 'Loaded!', $loader->loadTemplate() );
    }

    function testLoadFileFalseIfBadTemplate() {
        $loader = $this->get();
        assertFalse( $loader->loadFile() );
    }

    function testLoadFileFalseIfEmptyPath() {
        $loader = $this->get();
        $loader->shouldReceive( 'setDirectories' )->once()->with( FALSE )->andReturnNull();
        $loader->shouldReceive( 'getTemplateFile' )->once()->with( 'foo' )->andReturnNull();
        assertFalse( $loader->loadFile( 'foo' ) );
    }

    function testLoadFileNotMain() {
        $loader = $this->get();
        $loader->shouldReceive( 'setDirectories' )->once()->with( FALSE )->andReturnNull();
        $loader->shouldReceive( 'getTemplateFile' )->once()->with( 'foo' )->andReturn( '/path/to/foo' );
        $loader->shouldReceive( 'getFunction' )->once()->withNoArgs()->andReturn( function( $path ) {
            if ( $path !== '/path/to/foo' ) {
                throw new \RuntimeException;
            }
            return TRUE;
        } );
        assertTrue( $loader->loadFile( 'foo' ) );
    }

    function testLoadFileMain() {
        $GLOBALS[ 'wp_query' ] = FALSE;
        $loader = $this->get();
        $hooks = $loader->getHooks();
        $hooks->mock( 'index_template', '/path/to/foo/filter', function( $path ) {
            return $path === '/path/to/foo';
        } );
        $hooks->mock( 'template_include', '/path/to/foo/filter/filter', function($path) {
            return $path === '/path/to/foo/filter';
        } );
        $loader->shouldReceive( 'setDirectories' )->once()->with( TRUE )->andReturnNull();
        $loader->shouldReceive( 'getTemplateFile' )->once()->with( 'foo' )->andReturn( '/path/to/foo' );
        $loader->shouldReceive( 'getFunction' )
            ->once()
            ->withNoArgs()
            ->andReturn( function( $path ) {
                if ( $path !== '/path/to/foo/filter/filter' ) {
                    throw new \RuntimeException;
                }
                return TRUE;
            } );
        $loader->shouldReceive( 'doneAndExit' )
            ->once()
            ->with( 'template_loaded' )
            ->andReturn( 'Main Loaded!' );
        assertEquals( 'Main Loaded!', $loader->loadFile( 'foo', TRUE ) );
    }

    function testLoadFileMainUnfiltered() {
        \WP_Mock::wpFunction( 'did_action', [ 'return' => TRUE ] );
        $GLOBALS[ 'wp_query' ] = FALSE;
        $loader = $this->get();
        $loader->load( NULL, TRUE );
        $hooks = $loader->getHooks();
        $hooks->mock( 'index_template', '/path/to/foo/filter', function( $path ) {
            return $path === '/path/to/foo';
        } );
        $hooks->mock( 'template_include', '/path/to/foo/filter/filter', function($path) {
            return $path === '/path/to/foo/filter';
        } );
        $loader->shouldReceive( 'setDirectories' )->once()->with( TRUE )->andReturnNull();
        $loader->shouldReceive( 'getTemplateFile' )->once()->with( 'foo' )->andReturn( '/path/to/foo' );
        $loader->shouldReceive( 'getFunction' )
            ->once()
            ->withNoArgs()
            ->andReturn( function( $path ) {
                if ( $path !== '/path/to/foo' ) {
                    throw new \RuntimeException;
                }
                return TRUE;
            } );
        $loader->shouldReceive( 'doneAndExit' )
            ->once()
            ->with( 'template_loaded' )
            ->andReturn( 'Main Loaded!' );
        assertEquals( 'Main Loaded!', $loader->loadFile( 'foo', TRUE ) );
    }

    function testSetDirectories() {
        \WP_Mock::wpFunction( 'get_template_directory', [ 'return' => '/template/folder' ] );
        \WP_Mock::wpFunction( 'is_child_theme', [ 'return' => TRUE ] );
        \WP_Mock::wpFunction( 'get_stylesheet_directory', [ 'return' => '/stylesheet/folder' ] );
        $loader = $this->get();
        $hooks = $loader->getHooks();
        $hooks->mock( 'cortex.template_dirs', function( $def ) {
            $def[] = '/another/dir';
            return $def;
        } );
        $loader->setDirectories();
        $expected = [ '/stylesheet/folder', '/template/folder', '/another/dir' ];
        assertEquals( $expected, $loader->getDirectories() );
    }

    function testAddDir() {
        \WP_Mock::wpFunction( 'get_template_directory', [ 'return' => '/template/folder' ] );
        \WP_Mock::wpFunction( 'is_child_theme', [ 'return' => TRUE ] );
        \WP_Mock::wpFunction( 'get_stylesheet_directory', [ 'return' => '/stylesheet/folder' ] );
        $loader = $this->get();
        $loader->addDir( '/additional/path' );
        $expected = [ '/stylesheet/folder', '/template/folder', '/additional/path' ];
        assertEquals( $expected, $loader->getDirectories() );
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    function testGetTemplateFileFailsIfBadTemplate() {
        $loader = $this->get();
        $loader->getTemplateFile( TRUE );
    }

    function testGetTemplateSingle() {
        $loader = $this->get();
        $loader->shouldReceive( 'getTemplateFullPath' )
            ->atLeast( 1 )
            ->with( 'foo' )
            ->andReturn( '/path/to/foo' );
        assertEquals( '/path/to/foo', $loader->getTemplateFile( 'foo' ) );
    }

    function testGetTemplateMultiComma() {
        $loader = $this->get();
        $loader->shouldReceive( 'getTemplateFullPath' )
            ->atLeast( 1 )
            ->with( \Mockery::anyOf( 'foo', 'bar', 'baz' ) )
            ->andReturnUsing( function( $file ) {
                return ( $file === 'baz' ) ? '/path/to/baz' : '';
            }
        );
        assertEquals( '/path/to/baz', $loader->getTemplateFile( 'foo,bar,baz' ) );
    }

    function testGetTemplateMultiArray() {
        $loader = $this->get();
        $loader->shouldReceive( 'getTemplateFullPath' )
            ->atLeast( 1 )
            ->with( \Mockery::anyOf( 'foo', 'bar', 'baz' ) )
            ->andReturnUsing( function( $file ) {
                return ( $file === 'bar' ) ? '/path/to/bar' : '';
            }
        );
        assertEquals( '/path/to/bar', $loader->getTemplateFile( [ 'foo', 'bar', 'baz' ] ) );
    }

    function testGetTemplateMultiArrayFalse() {
        $loader = $this->get();
        $loader->shouldReceive( 'getTemplateFullPath' )
            ->atLeast( 1 )
            ->with( \Mockery::anyOf( 'foo', 'bar', 'baz' ) )
            ->andReturn( '' );
        assertFalse( $loader->getTemplateFile( [ 'foo', 'bar', 'baz' ] ) );
    }

    function testGetTemplateFullPath() {
        \WP_Mock::wpFunction( 'trailingslashit', [ 'return' => function( $path ) {
            $path = rtrim( $path, '\\/ ' );
            return $path . DIRECTORY_SEPARATOR;
        } ] );
        $dirs = [ '/srv/www', '/srv', __DIR__ ];
        $loader = $this->get();
        $loader->shouldReceive( 'getDirectories' )->withNoArgs()->andReturn( $dirs );
        assertEquals( __FILE__, $loader->getTemplateFullPath( basename( __FILE__ ) ) );
    }

}