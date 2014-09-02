<?php

class YAFPP extends Plugin implements IHandler
{
    private $host;

    function about()
    {
        return array(
            1.0,   // version
            'Replace feed contents by contents from the linked page using full-text-rss',   // description
            'schneefux'   // author
        );
    }

    function api_version()
    {
        return 2;
    }

    function init($host)
    {
        $this->host = $host;

        $host->add_hook($host::HOOK_PREFS_TAB, $this);
        $host->add_hook($host::HOOK_ARTICLE_FILTER, $this);
    }

    function csrf_ignore($method)
    {
        $csrf_ignored = array("index", "edit");
        return array_search($method, $csrf_ignored) !== false;
    }

    function before($method)
    {
        if ($_SESSION["uid"]) {
            return true;
        }
        return false;
    }

    function after()
    {
        return true;
    }

    function hook_article_filter($article)
    {
	$ftr = $this->host->get($this, "ftr_url");
	$url = trim($article['link']);
	$request = $ftr.'makefulltextfeed.php?format=json&url='.urlencode($url);

        if (version_compare(VERSION, '1.7.9', '>=')) {
            $result = fetch_file_contents($request);
        } else {
            // fallback to file_get_contents()
            $result = file_get_contents($request);
	}
        $result = json_decode($result, true);
        $content = $result['rss']['channel']['item']['description'];

        if (!(strcmp($content, "[unable to retrieve full-text content]") == 0)) {
	    $article['title'] = $result['rss']['channel']['item']['title'];
	    $article['content'] = $content;
        }
        return $article;
    }

    function hook_prefs_tab($args) {
             if ($args != "prefPrefs") return;

             print "<div dojoType=\"dijit.layout.AccordionPane\" title=\"".__("YAFPP: full-text-rss")."\">";

             print "<br/>";

             $ftr_url = $this->host->get($this, "ftr_url");
             print "<form dojoType=\"dijit.form.Form\">";

             print "<script type=\"dojo/method\" event=\"onSubmit\" args=\"evt\">
       evt.preventDefault();
       if (this.validate()) {
           console.log(dojo.objectToQuery(this.getValues()));
           new Ajax.Request('backend.php', {
                                parameters: dojo.objectToQuery(this.getValues()),
                                onComplete: function(transport) {
                                     notify_info(transport.responseText);
                                }
                            });
       }
       </script>";

            print "<input dojoType=\"dijit.form.TextBox\" style=\"display : none\" name=\"op\" value=\"pluginhandler\">";
            print "<input dojoType=\"dijit.form.TextBox\" style=\"display : none\" name=\"method\" value=\"save\">";
            print "<input dojoType=\"dijit.form.TextBox\" style=\"display : none\" name=\"plugin\" value=\"yafpp\">";
            print "<table width=\"100%\" class=\"prefPrefsList\">";
            print "<tr><td width=\"40%\">".__("URL to full-text-rss")."</td>";
            print "<td class=\"prefValue\"><input dojoType=\"dijit.form.ValidationTextBox\" required=\"true\" name=\"ftr_url\" regExp='^(http|https)://.*' value=\"$ftr_url\"></td></tr>";
            print "</table>";
            print "<p><button dojoType=\"dijit.form.Button\" type=\"submit\">".__("Save")."</button>";

            print "</form>";

            print "</div>"; #pane

    }


    function save()
    {
        $ftr_url = db_escape_string($_POST["ftr_url"]);
        $this->host->set($this, "ftr_url", $ftr_url);
        echo __("YAFPP configuration saved.");
    }

}
