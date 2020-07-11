<?php namespace web\unittest\io;

use io\streams\{Streams, MemoryOutputStream};
use io\{Folder, File, Files, Path};
use lang\Environment;
use unittest\TestCase;
use web\io\{Part, Stream};

class StreamTest extends TestCase {
  const NAME = 'test.txt';

  /**
   * Creates an iterable from given chunks
   *
   * @param  string... $chunks
   * @return iterable
   */
  private function asIterable(... $chunks) {
    foreach ($chunks as $chunk) {
      yield $chunk;
    }
  }

  /**
   * Creates a new fixture with given chunks
   *
   * @param  string $name
   * @param  string... $chunks
   * @return web.io.Stream
   */
  private function newFixture($name, ... $chunks) {
    return new Stream($name, 'text/plain', $this->asIterable(...$chunks));
  }

  /**
   * Assertion helper
   *
   * @param  [:string] $expected
   * @param  function(io.Folder): var
   * @throws unittest.AssertionFailedErrror
   */
  private function assertStored($expected, $target) {
    $t= new Folder(Environment::tempDir(), 'xp-web-streamtests');
    $t->create();

    try {
      $this->newFixture(self::NAME, 'Test')->store($target($t));

      $contents= [];
      foreach ($t->entries() as $name => $entry) {
        $contents[$name]= Files::read($entry->asFile());
      }
      $this->assertEquals($expected, $contents);
    } finally {
      $t->unlink();
    }
  }

  /** @return iterable */
  private function chunks() {
    yield [[], ''];
    yield [['Test'], 'Test'];
    yield [['Test', 'ed'], 'Tested'];
  }

  /** @return iterable */
  private function names() {
    yield ['.hidden', '.hidden'];
    yield ['test', 'test'];
    yield ['test.php', 'test.php'];
    yield ['C:\\autoexec.bat', 'autoexec.bat'];
    yield ['..\\test.txt', 'test.txt'];
    yield ['.\\test.txt', 'test.txt'];
    yield ['/etc/passwd', 'passwd'];
    yield ['../test.txt', 'test.txt'];
    yield ['./test.txt', 'test.txt'];
  }

  #[@test]
  public function can_create() {
    $this->newFixture(self::NAME);
  }

  #[@test]
  public function kind() {
    $this->assertEquals(Part::FILE, $this->newFixture(self::NAME)->kind());
  }

  #[@test, @values('names')]
  public function name($name, $base) {
    $this->assertEquals($base, $this->newFixture($name)->name());
  }

  #[@test, @values('names')]
  public function raw_name($name, $base) {
    $this->assertEquals($name, $this->newFixture($name)->name(true));
  }

  #[@test]
  public function type() {
    $this->assertEquals('text/plain', $this->newFixture(self::NAME)->type());
  }

  #[@test]
  public function string_representation() {
    $this->assertEquals('web.io.Stream("test", type= text/plain)', $this->newFixture('test')->toString());
  }

  #[@test, @values('chunks')]
  public function bytes($chunks, $expected) {
    $this->assertEquals($expected, $this->newFixture(self::NAME, ...$chunks)->bytes());
  }

  #[@test, @values('chunks')]
  public function read_all($chunks, $expected) {
    $this->assertEquals($expected, Streams::readAll($this->newFixture(self::NAME, ...$chunks)));
  }
}