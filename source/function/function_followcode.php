<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: function_followcode.php 35665 2015-11-06 04:09:22Z nemohou $
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

$_G['post_attach'] = array();

function fcodedisp($code, $type='codehtml') {
	global $_G;
	$_G['forum_discuzcode']['pcodecount']++;
	$_G['forum_discuzcode'][$type][$_G['forum_discuzcode']['pcodecount']] = $code;
	$_G['forum_discuzcode']['codecount']++;
	return "[\tD_".$_G['forum_discuzcode']['pcodecount']."\t]";
}

function followcode($message, $tid = 0, $pid = 0, $length = 0, $allowimg = true) {
	global $_G;

	include_once libfile('function/post');
	$message = strip_tags($message);
	$message = messagesafeclear($message);

	if((strpos($message, '[/code]') || strpos($message, '[/CODE]')) !== FALSE) {
		$message = preg_replace("/\s?\[code\](.+?)\[\/code\]\s?/is", "", $message);
	}

	$msglower = strtolower($message);

	$htmlon = 0;

	$message = dhtmlspecialchars($message);

	if($_G['setting']['plugins']['func'][HOOKTYPE]['discuzcode']) {
		$_G['discuzcodemessage'] = & $message;
		$param = func_get_args();
		hookscript('discuzcode', 'global', 'funcs', array('param' => $param, 'caller' => 'discuzcode'), 'discuzcode');
	}
	$_G['delattach'] = array();
	$message = fparsesmiles($message);

	if(strpos($msglower, 'attach://') !== FALSE) {
		$message = preg_replace("/attach:\/\/(\d+)\.?(\w*)/i", '', $message);
	}

	if(strpos($msglower, 'ed2k://') !== FALSE) {
		$message = preg_replace("/ed2k:\/\/(.+?)\//", '', $message);
	}
	if(strpos($msglower, '[/i]') !== FALSE) {
		$message = preg_replace("/\s*\[i=s\][\n\r]*(.+?)[\n\r]*\[\/i\]\s*/is", '', $message);
	}

	$message = str_replace('[/p]', "\n", $message);
	$message = str_replace(array(
		'[/color]', '[/backcolor]', '[/size]', '[/font]', '[/align]', '[b]', '[/b]', '[s]', '[/s]', '[hr]',
		'[i=s]', '[i]', '[/i]', '[u]', '[/u]', '[list]', '[list=1]', '[list=a]',
		'[list=A]', "\r\n[*]", '[*]', '[/list]', '[indent]', '[/indent]', '[/float]'
		), '', preg_replace(array(
		"/\[color=([#\w]+?)\]/i",
		"/\[color=((rgb|rgba)\([\d\s,]+?\))\]/i",
		"/\[backcolor=([#\w]+?)\]/i",
		"/\[backcolor=((rgb|rgba)\([\d\s,]+?\))\]/i",
		"/\[size=(\d{1,2}?)\]/i",
		"/\[size=(\d{1,2}(\.\d{1,2}+)?(px|pt)+?)\]/i",
		"/\[font=([^\[\<]+?)\]/i",
		"/\[align=(left|center|right)\]/i",
		"/\[float=left\]/i",
		"/\[float=right\]/i"
		), '', $message));

	if(strpos($msglower, '[/p]') !== FALSE) {
		$message = preg_replace("/\[p=(\d{1,2}|null), (\d{1,2}|null), (left|center|right)\]/i", "<p style=\"line-height:\\1px;text-indent:\\2em;text-align:left;\">", $message);
		$message = str_replace('[/p]', '</p>', $message);
	}

	if(strpos($msglower, '[/quote]') !== FALSE) {
		$message = preg_replace("/\s?\[quote\][\n\r]*(.+?)[\n\r]*\[\/quote\]\s?/is", '', $message);
	}
	if(strpos($msglower, '[/free]') !== FALSE) {
		$message = preg_replace("/\s*\[free\][\n\r]*(.+?)[\n\r]*\[\/free\]\s*/is", '', $message);
	}

	if(isset($_G['cache']['bbcodes'][-$allowbbcode])) {
		$message = preg_replace($_G['cache']['bbcodes'][-$allowbbcode]['searcharray'], '', $message);
	}
	if(strpos($msglower, '[/hide]') !== FALSE) {
		preg_replace("/\[hide.*?\]\s*(.*?)\s*\[\/hide\]/is", function($matches) {
            return hideattach($matches[1]);
        }, $message);
		if(strpos($msglower, '[hide]') !== FALSE) {
			$message = preg_replace("/\[hide\]\s*(.*?)\s*\[\/hide\]/is", '', $message);
		}
		if(strpos($msglower, '[hide=') !== FALSE) {
			$message = preg_replace("/\[hide=(d\d+)?[,]?(\d+)?\]\s*(.*?)\s*\[\/hide\]/is", '', $message);
		}
	}

	if(strpos($msglower, '[/url]') !== FALSE) {
		$message = preg_replace_callback("/\[url(=((https?|ftp|gopher|news|telnet|rtsp|mms|callto|bctp|thunder|qqdl|synacast){1}:\/\/|www\.|mailto:)?([^\r\n\[\"']+?))?\](.+?)\[\/url\]/is", function($matches) {
            return fparseurl($matches[1], $matches[5], $matches[2]);
        }, $message);
	}
	if(strpos($msglower, '[/email]') !== FALSE) {
		$message = preg_replace_callback("/\[email(=([a-z0-9\-_.+]+)@([a-z0-9\-_]+[.][a-z0-9\-_.]+))?\](.+?)\[\/email\]/is", function($matches) {
            return fparseemail($matches[1], $matches[4]);
        }, $message);
	}

	$nest = 0;
	while(strpos($msglower, '[table') !== FALSE && strpos($msglower, '[/table]') !== FALSE){
		$message = preg_replace_callback("/\[table(?:=(\d{1,4}%?)(?:,([\(\)%,#\w ]+))?)?\]\s*(.+?)\s*\[\/table\]/is", function($matches) {
            return fparsetable($matches[1], $matches[2], $matches[3]);
        }, $message);
		if(++$nest > 4) break;
	}

	if(strpos($msglower, '[/media]') !== FALSE) {
		$message = preg_replace_callback("/\[media=([\w,]+)\]\s*([^\[\<\r\n]+?)\s*\[\/media\]/is", function($matches) {
            return fparsemedia($matches[1], $matches[2]);
        }, $message);
	}
	if(strpos($msglower, '[/audio]') !== FALSE) {
		$message = preg_replace_callback("/\[audio(=1)*\]\s*([^\[\<\r\n]+?)\s*\[\/audio\]/is", function($matches) {
            return fparseaudio($matches[2]);
        }, $message);
	}
	if(strpos($msglower, '[/flash]') !== FALSE) {
		$message = preg_replace_callback("/\[flash(=(\d+),(\d+))?\]\s*([^\[\<\r\n]+?)\s*\[\/flash\]/is", function($matches) {
            return fparseflash($matches[4]);
        }, $message);
	}

	if($parsetype != 1 && strpos($msglower, '[swf]') !== FALSE) {
		$message = preg_replace_callback("/\[swf\]\s*([^\[\<\r\n]+?)\s*\[\/swf\]/is", function($matches) {
            return bbcodeurl($matches[1], ' <img src="'.STATICURL.'image/filetype/flash.gif" align="absmiddle" alt="" /> <a href="{url}" target="_blank">Flash: {url}</a> ');
        }, $message);
	}
	$flag = $length ? 1 : 0;
	if($tid) {
		$extra = "onclick=\"changefeed($tid, $pid, $flag, this)\"";
	}

	if(strpos($msglower, '[/img]') !== FALSE) {
		$patterns = array(
            "/\[img\]\s*([^\[\<\r\n]+?)\s*\[\/img\]/is",
            "/\[img=(\d{1,4})[x|\,](\d{1,4})\]\s*([^\[\<\r\n]+?)\s*\[\/img\]/is"
        );
        $callbacks = array(
            function($matches) use ($extra) {
                return fparseimg($matches[1], $extra);
            },
            function($matches) use ($extra) {
                return fparseimg($matches[3], $extra);
            }
        );
        foreach($patterns as $key => $p) {
            if($allowimg) $message = preg_replace_callback($p, $callbacks[$key], $message);
            else $message = preg_replace($p, '', $message);
        }
	}

	if($tid && $pid) {
		$_G['post_attach'] = C::t('forum_attachment_n')->fetch_all_by_id(getattachtableid($tid), 'pid', $pid);
		foreach($_G['post_attach'] as $aid => $attach) {
			if(!empty($_G['delattach']) && in_array($aid, $_G['delattach'])) {
				continue;
			}
			$message .= "[attach]$attach[aid][/attach]";
			$message = preg_replace("/\[attach\]$attach[aid]\[\/attach\]/i", fparseattach($attach['aid'], $length, $extra), $message, 1);
		}
	}

	if(strpos($msglower, '[/attach]') !== FALSE) {
		$message = preg_replace("/\[attach\]\s*([^\[\<\r\n]+?)\s*\[\/attach\]/is", '', $message);
	}
	$message = clearnl($message);

	if($length) {
		$sppos = strpos($message, chr(0).chr(0).chr(0));
		if($sppos !== false) {
			$message = substr($message, 0, $sppos);
		}
		$checkstr = cutstr($message, $length, '');
		if(strpos($checkstr, '[') && strpos(strrchr($checkstr, "["), ']') === FALSE) {
			$length = strpos($message, ']', strrpos($checkstr, strrchr($checkstr, "[")));
		}
		$message = cutstr($message, $length+1, ' <a href="javascript:;" class="flw_readfull xi2 xs1"'.$extra.'>'.lang('space', 'follow_view_fulltext').'</a>');
	} elseif($allowimg && !empty($extra)) {
		$message .= '<div class="ptm cl"><a href="javascript:;" class="flw_readfull y xi2 xs1"'.$extra.'>'.lang('space', 'follow_retract').'</a></div>';
	}

	for($i = 0; $i <= $_G['forum_discuzcode']['pcodecount']; $i++) {
		$code = '';
		if(isset($_G['forum_discuzcode']['codehtml'][$i]) && !empty($_G['forum_discuzcode']['codehtml'][$i])) {
			$code = $_G['forum_discuzcode']['codehtml'][$i];
		} elseif(!$length) {
			if(isset($_G['forum_discuzcode']['audio'][$i]) && !empty($_G['forum_discuzcode']['audio'][$i])) {
				$code = $_G['forum_discuzcode']['audio'][$i];
			} elseif(isset($_G['forum_discuzcode']['video'][$i]) && !empty($_G['forum_discuzcode']['video'][$i])) {
				$code = $_G['forum_discuzcode']['video'][$i];
			} elseif(isset($_G['forum_discuzcode']['media'][$i]) && !empty($_G['forum_discuzcode']['media'][$i])) {
				$code = $_G['forum_discuzcode']['media'][$i];
			} elseif(isset($_G['forum_discuzcode']['image'][$i]) && !empty($_G['forum_discuzcode']['image'][$i])) {
				$code = $_G['forum_discuzcode']['image'][$i];
			} elseif(isset($_G['forum_discuzcode']['attach'][$i]) && !empty($_G['forum_discuzcode']['attach'][$i])) {
				$code = $_G['forum_discuzcode']['attach'][$i];
			}
		}
		$message = str_replace("[\tD_$i\t]", $code, $message);
	}
	$message = clearnl($message);
	if(!empty($_GET['highlight'])) {
		$highlightarray = explode('+', $_GET['highlight']);
		$sppos = strrpos($message, chr(0).chr(0).chr(0));
		if($sppos !== FALSE) {
			$specialextra = substr($message, $sppos + 3);
			$message = substr($message, 0, $sppos);
		}
        $message = preg_replace_callback("/(^|>)([^<]+)(?=<|$)/sU", function($matches) {
            return highlightword($matches[2], \$highlightarray, $matches[1]);
        }, $message);
        $message = preg_replace("/<highlight>(.*)<\/highlight>/siU", "<strong><font color=\"#FF0000\">\\1</font></strong>", $message);
		if($sppos !== FALSE) {
			$message = $message.chr(0).chr(0).chr(0).$specialextra;
		}
	}

	unset($msglower);

	if($length) {
		$count = 0;
		$imagecode = $mediacode = $videocode = $audiocode = $mediahtml = '';
		for($i = 0; $i <= $_G['forum_discuzcode']['pcodecount']; $i++) {
			if(isset($_G['forum_discuzcode']['audio'][$i]) && !empty($_G['forum_discuzcode']['audio'][$i])) {
				$audiocode .= '<li>'.$_G['forum_discuzcode']['audio'][$attachcodei].'</li>';
			} elseif(isset($_G['forum_discuzcode']['video'][$i]) && !empty($_G['forum_discuzcode']['video'][$i])) {
				$videocode .= '<li>'.$_G['forum_discuzcode']['video'][$i].'</li>';
			} elseif(isset($_G['forum_discuzcode']['media'][$i]) && !empty($_G['forum_discuzcode']['media'][$i])) {
				$mediacode .= '<li>'.$_G['forum_discuzcode']['media'][$i].'</li>';
			} elseif(isset($_G['forum_discuzcode']['image'][$i]) && !empty($_G['forum_discuzcode']['image'][$i]) && $count < 4) {
				$imagecode .= '<li>'.$_G['forum_discuzcode']['image'][$i].'</li>';
				$count++;
			} elseif(isset($_G['forum_discuzcode']['attach'][$i]) && !empty($_G['forum_discuzcode']['attach'][$i])) {
				$attachcode .= '<li>'.$_G['forum_discuzcode']['attach'][$i].'</li>';
			}
		}
		if(!empty($audiocode)) {
			$message .= '<div class="flw_music"><ul>'.$audiocode.'</ul></div>';
		}
		if(!empty($videocode)) {
			$message .= '<div class="flw_video"><ul>'.$videocode.'</ul></div>';
		}
		if(!empty($mediacode)) {
			$message .= '<div class="flw_video"><ul>'.$mediacode.'</ul></div>';
		}
		if(!empty($imagecode)) {
			$message = '<div class="flw_image'.($count < 2 ? ' flw_image_1' : '').'"><ul>'.$imagecode.'</ul></div>'.$message;
		}
		if(!empty($attachcode)) {
			$message .= '<div class="flw_attach"><ul>'.$attachcode.'</ul></div>';
		}
	}
	return $htmlon ? $message : nl2br(str_replace(array("\t", '   ', '  '), ' ', $message));
}
function clearnl($message) {

	$message = preg_replace("/[\r\n|\n|\r]\s*[\r\n|\n|\r]/i", "\n", $message);
	$message = preg_replace("/^[\r\n|\n|\r]{1,}/i", "", $message);
	$message = preg_replace("/[\r\n|\n|\r]{2,}/i", "\n", $message);

	return $message;
}
function hideattach($hidestr) {
	global $_G;

	preg_match_all("/\[attach\]\s*(.*?)\s*\[\/attach\]/is", $hidestr, $del);
	foreach($del[1] as $aid) {
		$_G['delattach'][$aid] = $aid;
	}
}
function fparseurl($url, $text, $scheme) {
	global $_G;

	$html = '';
	if(!$url && preg_match("/((https?|ftp|gopher|news|telnet|rtsp|mms|callto|bctp|thunder|qqdl|synacast){1}:\/\/|www\.)[^\[\"']+/i", trim($text), $matches)) {
		$url = $matches[0];
		$length = 65;
		if(strlen($url) > $length) {
			$text = substr($url, 0, intval($length * 0.5)).' ... '.substr($url, - intval($length * 0.3));
		}
		$html = '<a href="'.(substr(strtolower($url), 0, 4) == 'www.' ? 'http://'.$url : $url).'" target="_blank">'.$text.'</a>';
	} else {
		$url = substr($url, 1);
		if(substr(strtolower($url), 0, 4) == 'www.') {
			$url = 'http://'.$url;
		}
		$url = !$scheme ? $_G['siteurl'].$url : $url;
		$atclass = substr(strtolower($text), 0, 1) == '@' ? ' class="xi2" ' : '';
		$html = '<a href="'.$url.'" target="_blank" '.$atclass.'>'.$text.'</a>';
	}
	return fcodedisp($html);
}

function fparseattach($aid, $length = 0, $extra = '') {
	global $_G;

	$html = '';
	if(!empty($_G['post_attach']) && !empty($_G['post_attach'][$aid])) {
		$attach = $_G['post_attach'][$aid];
		unset($_G['post_attach'][$attach['aid']]);
		$attach['url'] = ($attach['remote'] ? $_G['setting']['ftp']['attachurl'] : $_G['setting']['attachurl']).'forum/';
		$attach['isimage'] = $attach['isimage'] && !$attach['price'] ? $attach['isimage'] : 0;
		$attach['refcheck'] = (!$attach['remote'] && $_G['setting']['attachrefcheck']) || ($attach['remote'] && ($_G['setting']['ftp']['hideurl'] || ($attach['isimage'] && $_G['setting']['attachimgpost'] && strtolower(substr($_G['setting']['ftp']['attachurl'], 0, 3)) == 'ftp')));
		$rimg_id = random(5).$attach['aid'];
		if($attach['isimage'] && !$attach['price'] && !$attach['readperm']) {
			$nothumb = $length ? 0 : 1;
			$src = $attach['url'].(!$attach['thumb'] ? $attach['attachment'] : getimgthumbname($attach['attachment']));
			$html = bbcodeurl($src, '<img id="aimg_'.$rimg_id.'" src="'.$src.'" border="0" alt="'.$attach['filename'].'" '.$extra.' style="cursor: pointer;" />');

			return fcodedisp($html, 'image');
		} else {
			if($attach['price'] || $attach['readperm']) {
				$html = '<a href="forum.php?mod=viewthread&tid='.$attach['tid'].'" id="attach_'.$rimg_id.'" target="_blank" class="flw_attach_price"><strong>'.$attach['filename'].'</strong><span>'.sizecount($attach['filesize']).'</span></a>';
			} else {
				require_once libfile('function/attachment');
				$aidencode = packaids($attach);
				$attachurl = "forum.php?mod=attachment&aid=$aidencode";
				$html = '<a href="'.$attachurl.'" id="attach_'.$rimg_id.'"><strong>'.$attach['filename'].'</strong><span>'.sizecount($attach['filesize']).'</span></a>';
			}
			return fcodedisp($html, 'attach');
		}
	}
	return '';
}

function fparseflash($url) {
	preg_match("/((https?){1}:\/\/|www\.)[^\[\"']+/i", $url, $matches);
	$url = $matches[0];
	if(fileext($url) != 'flv') {
		$rimg_id = 'swf_'.random(5);
		$html = bbcodeurl($url, '<img src="'.IMGDIR.'/flash.gif" alt="'.lang('space', 'follow_click_play').'" onclick="javascript:showFlash(\'flash\', \''.$url.'\', this, \''.$rimg_id.'\');" class="tn" style="cursor: pointer;" />');
		return fcodedisp($html, 'media');
	} else {
		$url = STATICURL.'image/common/flvplayer.swf?&autostart=true&file='.urlencode($matches[0]);
		return fmakeflv($url);
	}
}

function fparseemail($email, $text) {
	global $_G;

	$text = str_replace('\"', '"', $text);
	$html = '';
	if(!$email && preg_match("/\s*([a-z0-9\-_.+]+)@([a-z0-9\-_]+[.][a-z0-9\-_.]+)\s*/i", $text, $matches)) {
		$email = trim($matches[0]);
		$html = '<a href="mailto:'.$email.'">'.$email.'</a>';
	} else {
		$html = '<a href="mailto:'.substr($email, 1).'">'.$text.'</a>';
	}
	return fcodedisp($html);
}

function fparsetable($width, $bgcolor, $message) {
	global $_G;
	$html = '';
	if(strpos($message, '[/tr]') === FALSE && strpos($message, '[/td]') === FALSE) {
		$rows = explode("\n", $message);
		$html = '<table cellspacing="0" class="t_table" '.
			($width == '' ? NULL : 'style="width:'.$width.'"').
			($bgcolor ? ' bgcolor="'.$bgcolor.'">' : '>');
		foreach($rows as $row) {
			$html .= '<tr><td>'.str_replace(array('\|', '|', '\n'), array('&#124;', '</td><td>', "\n"), $row).'</td></tr>';
		}
		$html .= '</table>';
	} else {
		if(!preg_match("/^\[tr(?:=([\(\)\s%,#\w]+))?\]\s*\[td([=\d,%]+)?\]/", $message) && !preg_match("/^<tr[^>]*?>\s*<td[^>]*?>/", $message)) {
			return str_replace('\\"', '"', preg_replace("/\[tr(?:=([\(\)\s%,#\w]+))?\]|\[td([=\d,%]+)?\]|\[\/td\]|\[\/tr\]/", '', $message));
		}
		if(substr($width, -1) == '%') {
			$width = substr($width, 0, -1) <= 98 ? intval($width).'%' : '98%';
		} else {
			$width = intval($width);
			$width = $width ? ($width <= 560 ? $width.'px' : '98%') : '';
		}
        $patterns = array(
            "/\[tr(?:=([\(\)\s%,#\w]+))?\]\s*\[td(?:=(\d{1,4}%?))?\]/i",
            "/\[\/td\]\s*\[td(?:=(\d{1,4}%?))?\]/i",
            "/\[tr(?:=([\(\)\s%,#\w]+))?\]\s*\[td(?:=(\d{1,2}),(\d{1,2})(?:,(\d{1,4}%?))?)?\]/i",
            "/\[\/td\]\s*\[td(?:=(\d{1,2}),(\d{1,2})(?:,(\d{1,4}%?))?)?\]/i",
        );
        $callbacks = array(
            function($matches) {
                return parsetrtd($matches[1], '0', '0', $matches[2]);
            },
            function($matches) {
                return parsetrtd('td', '0', '0', $matches[1]);
            },
            function($matches) {
                return parsetrtd($matches[1], $matches[2], $matches[3], $matches[4]);
            },
            function($matches) {
                return parsetrtd('td', $matches[1], $matches[2], $matches[3]);
            }
        );
        foreach($patterns as $key => $p) {
            $message = preg_replace_callback($p, $callbacks[$key], $message);
        }
		$html = '<table cellspacing="0" class="t_table" '.
			($width == '' ? NULL : 'style="width:'.$width.'"').
			($bgcolor ? ' bgcolor="'.$bgcolor.'">' : '>').
			str_replace('\\"', '"', preg_replace("/\[\/td\]\s*\[\/tr\]\s*/i", '</td></tr>', $message)
			).'</table>';
	}
	return fcodedisp($html);

}

function fparseaudio($url) {
	$url = addslashes($url);
        if(!in_array(strtolower(substr($url, 0, 6)), array('http:/', 'https:', 'ftp://', 'rtsp:/', 'mms://')) && !preg_match('/^static\//', $url) && !preg_match('/^data\//', $url)) {
		return dhtmlspecialchars($url);
	}
	if(fileext($url) == 'mp3') {
		$randomid = 'music_'.random(3);
		$html = '<img src="'.IMGDIR.'/music.gif" alt="'.lang('space', 'follow_click_play').'" onclick="javascript:showFlash(\'music\', \''.$url.'\', this, \''.$randomid.'\');" class="tn" style="cursor: pointer;" />';
		return fcodedisp($html, 'audio');
	} else {
		$html = '<a href="'.$url.'" target="_blank">'.$url.'</a>';
		return $html;
	}


}
function fmakeflv($flv) {
	$randomid = 'video_'.random(3);
	$flv = is_array($flv) ? $flv : array('flv' => $flv);
	if(!preg_match("/^((https?){1}:\/\/|www\.)[^\[\"']+$/i", $flv['flv'])) {
		return fcodedisp('', 'video');
	}
	if(!empty($flv['imgurl'])) {
		if(!preg_match("/^((https?){1}:\/\/|www\.)[^\[\"']+$/i", $flv['imgurl'])) {
			$html = '';
		} else {
			$html = '<table class="mtm" title="'.lang('space', 'follow_click_play').'" onclick="javascript:showFlash(\'flash\', \''.$flv['flv'].'\', this, \''.$randomid.'\');"><tr><td class="vdtn hm" style="background: url('.$flv['imgurl'].') no-repeat;    border: 1px solid #CDCDCD; cursor: pointer; height: 95px; width: 126px;"><img src="'.IMGDIR.'/vds.png" alt="'.lang('space', 'follow_click_play').'" />	</td></tr></table>';
		}
	} else {
		$html = '<img src="'.IMGDIR.'/vd.gif" alt="'.lang('space', 'follow_click_play').'" onclick="javascript:showFlash(\'flash\', \''.$flv['flv'].'\', this, \''.$randomid.'\');" class="tn" style="cursor: pointer;" />';
	}
	return fcodedisp($html, 'video');
}
function fparsemedia($params, $url) {
	$params = explode(',', $params);

	$url = addslashes($url);
	$html = '';
	if($flv = parseflv($url, 0, 0)) {
		return fmakeflv($flv);
	}
	if(in_array(count($params), array(3, 4))) {
		$type = $params[0];
		$url = str_replace(array('<', '>'), '', str_replace('\\"', '\"', $url));
		switch($type) {
			case 'mp3':
				return fparseaudio($url);
				break;
			case 'flv':
				$url = STATICURL.'image/common/flvplayer.swf?&autostart=true&file='.urlencode($url);
				return fmakeflv($url);
				break;
			case 'swf':
				return fparseflash($url);
				break;
			default:
				$html = '<a href="'.$url.'" target="_blank">'.$url.'</a>';
				break;
		}
	}
	return fcodedisp($html, 'media');
}

function fparseimg($src, $extra = '') {
	global $_G;

	$rimg_id = random(5);
	$html = bbcodeurl($src, '<img id="iimg_'.$rimg_id.'" src="'.$src.'" border="0" alt="" '.$extra.' style="cursor: pointer;" />');
	return fcodedisp($html, 'image');
}
function fparsesmiles(&$message) {
	global $_G;
	static $enablesmiles;
	if($enablesmiles === null) {
		$enablesmiles = false;
		if(!empty($_G['cache']['smilies']) && is_array($_G['cache']['smilies'])) {
			foreach($_G['cache']['smilies']['replacearray'] AS $key => $smiley) {
				if(substr($_G['cache']['smilies']['replacearray'][$key], 0, 1) == '<') {
					break;
				}
				$_G['cache']['smilies']['replacearray'][$key] = '<img src="'.STATICURL.'image/smiley/'.$_G['cache']['smileytypes'][$_G['cache']['smilies']['typearray'][$key]]['directory'].'/'.$smiley.'" smilieid="'.$key.'" border="0" class="s" alt="" />';
			}
			$enablesmiles = true;
		}
	}
	$enablesmiles && $message = preg_replace($_G['cache']['smilies']['searcharray'], $_G['cache']['smilies']['replacearray'], $message, $_G['setting']['maxsmilies']);
	return $message;
}

?>