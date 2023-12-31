<?php

namespace Tests\Unit\Helpers;

use App\Helpers\ZipReadHelper;
use PHPUnit\Framework\TestCase;

class ZipReadHelperTest extends TestCase
{
    /**
     * read_payke_version test.
     */
    public function test_read_payke_version(): void
    {
        $zh = new ZipReadHelper();
        // $r1 = $zh->read_payke_version("storage/payke_resources/zips/payke-ec-cae6ae8bf6d3.zip");
        // $this->assertEquals("v3.22.2", $r1);

        $r2 = $zh->read_payke_version("storage/app/payke_resources/zips/payke-ec-752d7ee2ff92.zip");
        $this->assertEquals("v3.21.7.1", $r2);

        $vs = explode(".", $r2);
        $vx = substr($vs[0], 1, strlen($vs[0])-1);
        $vy = $vs[1];
        $vz = $vs[2];
        // print("{$r2} ---> x {$vx}  y {$vy}  z {$vz}");

        $r3 = $zh->read_payke_version("storage/app/payke_resources/zips/payke-ec-6d979f64ed30.zip");
        $this->assertEquals("v3.23.1", $r3);
    }

    /**
     * read_payke_version test.
     */
    public function test_read_payke_migrations(): void
    {
        $zh = new ZipReadHelper();
        $r1 = $zh->read_payke_migrations("storage/app/payke_resources/zips/payke-ec-752d7ee2ff92.zip");
        // print_r($r1);
        $this->assertEquals("1688897142, 1688897142_add_some_columns_and_index_to_payments", $r1[count($r1) - 1]);
    }

    /**
     * read_payke_version_all test.
     */
    public function test_read_payke_migrations_all(): void
    {
        print("--------------------------------------------------\n");
        $zh = new ZipReadHelper();
        $zips = glob('storage/app/payke_resources/zips/*.zip');
        $versions = array_fill(0, count($zips), "");
        for($i=0; $i<count($zips); $i++)
        {
            $versions[$i] = $zh->read_payke_version($zips[$i]);
        }
        arsort($versions);
        $stopname = "";
        $migration_vers = [];
        foreach ($versions as $key => $val) {
            $migrations = $zh->read_payke_migrations($zips[$key]);
            if($stopname == "") $stopname = $migrations[count($migrations) - 7];
            for ($i = 1; $i < 10; $i++) { 
                $migration = $migrations[count($migrations) - $i];
                $migration_vers[$migration] = $val;
                if($migration == $stopname) break;
            }
        }
        foreach ($migration_vers as $key => $val) {
            print("{$val} : {$key}\n");
        }
    }
}