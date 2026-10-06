<?php
/**
 * Tag Autocomplete for FlatBB
 */
if (!defined('FLATBB')) exit;

function tag_autocomplete_assets(string $value, array $ctx): string
{
    $page = (string)($ctx['page'] ?? ($ctx['route'] ?? ''));
    $requestUri = (string)($_SERVER['REQUEST_URI'] ?? '');
    $isComposer = (bool)preg_match('/(new|create|topic|thread|compose|edit)/i', $page . ' ' . $requestUri);
    if (!$isComposer) return $value;

    $tags = [];
    try {
        $pdo = function_exists('db') ? db() : null;
        if ($pdo instanceof PDO) {
            $rows = $pdo->query('SELECT id, name FROM fb_tags ORDER BY name ASC LIMIT 200')->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $row) {
                $name = trim((string)($row['name'] ?? ''));
                if ($name !== '') $tags[] = ['id' => (int)$row['id'], 'name' => $name];
            }
        }
    } catch (Throwable $e) {}

    $json = json_encode($tags, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?: '[]';
    $css = '<style>.ta-menu{position:absolute;z-index:999;top:100%;left:0;right:0;display:none;max-height:220px;overflow:auto;background:#fff;border:1px solid #ccc;border-radius:6px;padding:3px;box-shadow:0 5px 15px #0002}.ta-menu.open{display:block}.ta-item{display:block;width:100%;padding:8px;border:0;background:transparent;color:inherit;cursor:pointer;text-align:right;font:inherit}.ta-item:hover,.ta-item.active{background:#eee}</style>';
    $js = <<<'JS'
(function(){var tags=__TAGS__;function n(s){return String(s||'').trim().toLocaleLowerCase()}function init(i){if(i.dataset.ta)return;i.dataset.ta=1;var p=i.parentNode;if(getComputedStyle(p).position==='static')p.style.position='relative';var m=document.createElement('div');m.className='ta-menu';p.appendChild(m);var a=0;function seg(){return i.value.split(/[,،]/)}function res(){var q=n(seg().slice(-1)[0]),u=seg().slice(0,-1).map(n).filter(Boolean);return tags.filter(function(x){return u.indexOf(n(x.name))<0&&(!q||n(x.name).indexOf(q)>=0)}).slice(0,5)}function paint(){m.querySelectorAll('.ta-item').forEach(function(x,k){x.classList.toggle('active',k===a);if(k===a)x.scrollIntoView({block:'nearest'})})}function close(){m.classList.remove('open');m.innerHTML='';a=0}function show(){var l=res();m.innerHTML='';if(!l.length){close();return}a=0;l.forEach(function(x){var b=document.createElement('button');b.type='button';b.className='ta-item';b.textContent=x.name;b.addEventListener('mousedown',function(e){e.preventDefault();choose(x)});m.appendChild(b)});m.classList.add('open');paint()}function choose(x){var s=seg();s[s.length-1]=' '+x.name;i.value=s.join(',').replace(/^\s+/,'')+', ';i.focus();show()}i.addEventListener('focus',show);i.addEventListener('input',show);i.addEventListener('keydown',function(e){var z=m.querySelectorAll('.ta-item');if(e.key==='ArrowDown'&&z.length){e.preventDefault();a=(a+1)%z.length;paint()}else if(e.key==='ArrowUp'&&z.length){e.preventDefault();a=(a-1+z.length)%z.length;paint()}else if(e.key==='Enter'&&m.classList.contains('open')&&z[a]){e.preventDefault();choose(res()[a])}else if(e.key==='Escape')close()});document.addEventListener('click',function(e){if(!p.contains(e.target))close()})}function scan(){document.querySelectorAll('input[name="tags"],input[name="tag"],input[name="tags[]"],textarea[name="tags"],textarea[name="tag"]').forEach(init)}scan();if(window.MutationObserver)new MutationObserver(scan).observe(document.body,{childList:true,subtree:true})})()
JS;
    $js = str_replace('__TAGS__', $json, $js);
    $script = function_exists('script_tag') ? script_tag($js) : '<script>' . $js . '</script>';
    return $value . $css . $script;
}

return [
    'id' => 'tag_autocomplete',
    'name' => 'Tag Autocomplete',
    'version' => '1.0.1',
    'description' => 'Lightweight tag autocomplete for FlatBB.',
    'author' => 'Milad Cheraghi',
    'requires' => ['flatbb' => '0.1.50'],
    'hooks' => ['region.footer.right' => 'tag_autocomplete_assets'],
];
