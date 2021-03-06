<?php

/**
 * @group functional
 * @group legacy
 */
class report_reportTest extends \report_abstractReportTestCase
{
    protected $ret;
    protected $dmin;
    protected $dmax;
    protected $saved_sbasid;

    /**
     *
     * @var module_report
     */
    protected $report;
    protected $save_report;

    public function setUp()
    {
        parent::setUp();
        $this->xml = '<?xml version="1.0" encoding="UTF-8"?>
        <record record_id="299">
          <description>
            <report ok="1">hello</report>
          </description>
         </record>';
        $date = new Datetime();
        $this->dmax = $date->format("Y-m-d H:i:s");
        $date->modify('-6 month');
        $this->dmin = $date->format("Y-m-d H:i:s");
        $databoxes = self::$DI['app']['phraseanet.appbox']->get_databoxes();
        $this->ret = [];
        foreach ($databoxes as $databox) {
            $colls = $databox->get_collections();
            $rett = [];
            foreach ($colls as $coll) {
                $rett[$coll->get_coll_id()] = $coll->get_coll_id();
            }
            $this->ret[$databox->get_sbas_id()] = implode(',', $rett);
        }
    }

    public function testReport()
    {
        foreach ($this->ret as $sbasid => $collections) {
            $this->report = new module_report(self::$DI['app'], $this->dmin, $this->dmax, $sbasid, $collections);
            $this->report->setUser_id(self::$DI['user']->getId());
            $this->assertEquals($collections, $this->report->getListCollId());
            $this->host($this->report);
        }
    }

    public function host($report)
    {
        $host = 'http://www.google.fr/search?q=helloworld&ie=utf-8&oe=utf-8&aq=t&rls=org.mozilla:fr:official&client=firefox-a#pq=fake%20url%20constructor&hl=fr&sugexp=gsnos%2Cn%3D2&cp=8&gs_id=y&xhr=t&q=hello+world&pf=p&sclient=psy&client=firefox-a&hs=mIa&rls=org.mozilla:fr%3Aofficial&source=hp&pbx=1&oq=hello+wo&aq=0&aqi=g2&aql=t&gs_sm=&gs_upl=&bav=on.2,or.r_gc.r_pw.&fp=ab54cb1d4456efee&biw=1152&bih=712';
        $host = $report->getHost($host);
        $this->assertEquals('www.google.fr', $host);
        $host = 'http://localhost.phr4/login';
        $host = $report->getHost($host);
        $this->assertEquals('localhost.phr4', $host);
    }

    public function SqlBuilder()
    {
        $this->markTestSkipped('to refactor');
        $domain = $this->report->sqlBuilder("connexion");
        $this->assertInstanceOf('module_report_sqlconnexion', $domain);
        $domain = $this->report->sqlBuilder("download");
        $this->assertInstanceOf('module_report_sqldownload', $domain);
        $domain = $this->report->sqlBuilder("question");
        $this->assertInstanceOf('module_report_sqlquestion', $domain);
        $domain = $this->report->sqlBuilder("action");
        $this->assertInstanceOf('module_report_sqlaction', $domain);
        $domain = $this->report->sqlBuilder("unknow");
        $this->assertEquals($this->report->getReq(), $domain);
    }

    public function SetBound()
    {
        $this->report->setBound('test', true);
        $bound = $this->report->getBound();
        $this->assertArrayhaskey('test', $bound);
        $this->assertEquals(1, $bound['test']);
        $this->report->setBound('test', false);
        $bound = $this->report->getBound();
        $this->assertArrayhaskey('test', $bound);
        $this->assertEquals(0, $bound['test']);
    }

    public function SetOrder()
    {
        $this->report->setOrder('champs', 'order');
        $order = $this->report->getOrder();
        $this->assertEquals('champs', $order['champ']);
        $this->assertEquals('order', $order['order']);
        $this->assertEquals('champs', $this->report->getOrder('champ'));
    }

    public function testGetterSetter()
    {
        $report = new module_report(self::$DI['app'], $this->dmin, $this->dmax, 1, '');
        $bool = true;
        $report->setPrettyString($bool);
        $this->assertEquals($bool, $report->getPrettyString());
        $title = 'test';
        $report->setTitle($title);
        $this->assertEquals($title, $report->getTitle());
        $bool = false;
        $report->setCsv($bool);
        $this->assertEquals($bool, $report->getCsv());
        $filter = ['test', 'array'];
        $report->setFilter($filter);
        $this->assertEquals($filter, $report->getTabFilter());
        $periode = "2 years";
        $report->setPeriode($periode);
        $this->assertEquals($periode, $report->getPeriode());
        $postingFilter = 'my posting filter !';
        $report->setpostingFilter($postingFilter);
        $this->assertEquals($postingFilter, $report->getPostingFilter());
        $page = 223;
        $limit = 125;
        $report->setLimit($page, $limit);
        $this->assertEquals($page, $report->getNbPage());
        $this->assertEquals($limit, $report->getNbRecord());
        $report->setGroupBy($bool);
        $this->assertEquals($bool, $report->getGroupBy());
        $column = ['col1', 'col2'];
        $report->setActiveColumn($column);
        $this->assertEquals($column, $report->getActiveColumn());
        $report->setConfig($bool);
        $report->setPrint($bool);
        $report->setHasLimit($bool);
        $this->assertFalse($report->getConfig());
        $this->assertFalse($report->getPrint());
        $this->assertFalse($report->getHasLimit());
        $result = ['result', 'result'];
        $report->setResult($result);
        $this->assertEquals($result, $report->getResult());
        $total = 3200;
        $report->setTotal($total);
        $this->assertEquals($total, $report->getTotal());
        $default_display = ['a', 'b', 'c'];
        $report->setDefault_display($default_display);
        $this->assertEquals($default_display, $report->getDefault_display());
    }

    public function testOther()
    {

        foreach ($this->ret as $sbasid => $collections) {
            $report = $this->getMock('module_report', ['buildReq', 'buildResult'], [], '', FALSE);
            $report->setSbas_id($sbasid);
            $this->assertEquals($sbasid, $report->getSbas_id());

            $r = new \ReflectionObject($report);
            $p = $r->getProperty('app');
            $p->setAccessible(true);
            $p->setValue($report, self::$DI['app']);

            $report->setRequest('SELECT
          user,
          usrid,
          log.date as ddate,
          log.societe,
          log.pays,
          log.activite,
          log.fonction,
          site,
          sit_session,
          appli,
          ip
         FROM log ');
            $report->expects($this->any())->method('buildReq')->will($this->returnValue(''));
            $report->expects($this->any())->method('buildResult')->will($this->returnValue([]));
            $result = $report->buildReport(false, 'user');

        }
    }
}
