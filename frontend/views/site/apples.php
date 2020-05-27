<?php
/**
 * Для того чтобы интерфейс смотрелся более динамично лучше было бы сделать обновление списка яблок через
 * pjax(как вариант) после каждой операции, но это дольше, а у нас всё таки тестовое задание.
 * Поэтому после каждой операции перезагрузка страницы и редирект на список.
 *
 * Стили не в отдельных файлах для упрощения.
 * По той же причине картинки не вырезаны под нужный размер, а уменьшины через css
 *
 * Вообще js/css обычно делаю через ассеты
 *
 */

 /**@var $apples \common\models\Apple[]*/

?>

<table class="table">
    <thead>
    <tr>
        <th scope="col">Идентификатор</th>
        <th scope="col">Вид</th>
        <th scope="col">Состояние</th>
        <th scope="col">Операции</th>
    </tr>
    <tbody>
    <?php foreach ($apples as $apple):?>
        <tr>
            <td><?=$apple->id?></td>
            <td><img title="Остаток: <?=$apple->remain?>%" src="<?=$apple->getUrl()?>" style="width: 20px;height: 20px;opacity: <?=$apple->getOpacity() ?>"></td>
            <td><?=$apple->state()?></td>
            <td>
                <button onclick="location.href = 'fall/<?=$apple->id?>'" class="btn-success">Уронить</button>
                <button onclick="eat(<?=$apple->id?>)"
                        class="btn-success">Откусить %</button>
                <input  id="apple_input_<?=$apple->id?>" type="number" pattern="\d+" step="1" min="0" max="100"
                        onkeypress="return event.charCode >= 48 && event.charCode <= 57"/>
            </td>
        </tr>
    <?php endforeach;?>
    </tbody>
</table>
<?php if(Yii::$app->session->hasFlash('appleError')):?>
    <div class="alert alert-danger" role="alert">
        <?=Yii::$app->session->getFlash('appleError')?>
    </div>
<?endif;?>
<button class="btn-danger" onclick="location.href = 'generate'">Посадить заново</button>
<script>
    function eat(appleId) {
        let part = document.getElementById('apple_input_' + appleId).value;
        if (part.length) {
            location.href = 'eat/' + appleId + '/' + part;
        }
    }
</script>

