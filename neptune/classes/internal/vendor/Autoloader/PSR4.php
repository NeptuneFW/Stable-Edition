<?php

class Psr4
{
  // Tüm namespace değerlerinin tutulacağı dizi.
  private $namespaces = [];

  /*
   * Yeni bir namespace ekleme işlemi yapmak için kullanılan fonksiyon.
   *
   * @namespace: Sınıflar hangi namespaceyi kullanacak.
   * Örnek:
   *  Neptune\QuickDB namespace ve sınıfı da
   *  Neptune\QuickDB\QuickDBQueryBuilder::generate($query);
   *  Belirttiğimiz namespace değeri olduğu takdirde PSR4 yükleme başarılı olur.
   *
   * @baseDir: Namespace değeri hangi dosya içerisinde aranacak.
   * Örnek:
   *  Namespace: Neptune\QuickDB olsun fakat dosyamız
   *  neptune/classes/internal/vendor/Database/QuickDB
   *  Bu şekilde bir kullanım yapılabilir. İlla namespace ile aynı dizin yapısına
   *  sahip olacak diye bir kural yoktur.
  */
  public function addNamespace($namespace, $baseDir)
  {
    /*
     * $trim  : Namespace değerinde ki "\\" karakterini hem baştan hemde soldan siler.
     * $rtrim : BaseDir değerinde ki "\ (DS === DIRECTORY_SEPARATOR)" karakterini
     *          sadece sağdan siler.
    */
    $trim = trim($namespace, '\\');
    $rtrim = rtrim($baseDir, DS);

    /*
     * $namespace : Üstte tanımladığımız trim değişkenini kullanarak sonuna "\\" ekler.
     * $baseDir   : Üstte tanımladığımız rtrim değişkeni kullanarak sonuna
     *              "\ (DS === DIRECTORY_SEPARATOR)" ekler.
    */
    $namespace = $trim.'\\';
    $baseDir = $rtrim.DS;

    /*
     * Private olarak tanımladığımız namespaces dizisine üstte tanımladığımız
     * $namespace & $baseDir değişkenlerini atar.
    */
    $this->namespaces[] = [
      $namespace,
      $baseDir,
    ];
  }

  /*
   * Kısaca özetlemek gerekirse, içerisine girdiğimiz dosya adını arar ve geri döndürür.
   * Aşağıda f(loadClass) fonksiyonunda bu fonksiyona yer verilmiştir.
  */
  public function findFile($class)
  {
    // Aldığı değere göre başında ki "\\" karakterini siler.
    $class = ltrim($class, '\\');

    /*
     * Private olarak tanımladığımız namespaces dizisini bir döngüye sokar.
     * Ve PHP'nin f(list) fonksiyonu ile de bu namespace değerlerini listeler.
     * Eğer list hakkında daha detaylı bilgi isterseniz:
     *  http://php.net/manual/en/function.list.php
    */
    foreach ($this->namespaces as list($currentNamespace, $currentBaseDir)) {
      /*
       * Eğer ki class değişkeni ile şu an kullanılan namespace değeri
       * 0 değeri döndürüyorsa ki bu da eşleşmediği anlamına gelir
       * bu if içerisine girerek işlemine devam eder.
       * Örnek:
       *  $class            : Autoloader\ClassMap
       *  $currentNamespace : Autoloader\
       *  f(strpos)         : 0
       *  O zaman if'in içerisine girecektir.
      */
      if (strpos($class, $currentNamespace) === 0) {
        /*
         * En önemli aşamalardan birisi budur. Direk sınıf ismini alıyoruz.
         * Namespace'nin uzunluğunu öğrenip substr fonksiyonu ile
         * bunu kaldırıyoruz.
        */
        $classRealName = substr($class, strlen($currentNamespace));

        /*
         * Sınıfın gerçek isminde ki "\\" karakterini direk DS define
         * değeri ile değiştiriyor.
        */
        $replace = str_replace('\\', DS, $classRealName);

        /*
         * Hatırlarsanız döngüde list fonksiyonunu kullanmıştık ve içerisine
         * $currentBaseDir diye bir değişken girmiştik. Bu bize direk
         * sınıfın bulunduğu dizini veriyor. Ve bunu üstte belirttiğimiz
         * replace değişkeni ile birleştirip sonuna ".php" string değerini
         * ekliyor. Bu da direk dosya olmuş oluyor.
        */
        $file = $currentBaseDir.$replace.'.php';

        // Oluşturduğumuz dosya bulunuyor mu diye kontrol ediyoruz.
        if (file_exists($file)) {
          // Dosya var ise geriye döndürüyor.
          return $file;
        }
      }
    }
  }

  // Harici olarak belirttiğimiz bir sınıfı yüklemeye yarıyor.
  public function loadClass($class)
  {
    // Üstte yazdığımız f(findFile) fonksiyonu ile sınıfı buluyoruz.
    $file = $this->findFile($class);

    // Eğer ki sınıf var ise bunu çağırıyor. Ve return olarak true döndürüyor.
    if ($file !== null) {
      require $file;

      return true;
    }

    // Sınıf yok ise geriye false bir değer döndürüyor.
    return false;
  }
  public static function classPrivilege($class, $privilege)
  {
    if (class_exists($class)) {
      class_alias($class, $privilege);
    }
  }

  // Sınıfların yüklenmesini sağlıyoruz.
  public function register()
  {
    spl_autoload_register([
      $this,
      'loadClass',
    ], true);
  }

  // Sınıfların yüklenmesini kaldırıyoruz.
  public function unregister()
  {
    spl_autoload_unregister([
      $this,
      'loadClass',
    ]);
  }
}
