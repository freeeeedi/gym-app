<?php

/*
 * Блюда из продуктов каталога. КБЖУ порции не хранится — считается из ингредиентов.
 * meals — для каких приёмов пищи; servings — на сколько порций рецепт;
 * ingredients — slug продукта => граммы на весь рецепт (сырой вес).
 */

return [
    // --- Завтраки ---
    [
        'slug' => 'oatmeal-banana-nuts', 'name' => 'Овсянка на молоке с бананом и орехами',
        'meals' => ['breakfast'], 'servings' => 1, 'minutes' => 10,
        'ingredients' => ['oats' => 60, 'milk-2-5' => 200, 'banana' => 100, 'walnuts' => 15],
        'recipe' => 'Залейте хлопья молоком, доведите до кипения и варите 5 минут, помешивая. Добавьте нарезанный банан и рубленые орехи.',
    ],
    [
        'slug' => 'vegetable-omelet', 'name' => 'Омлет с овощами и сыром',
        'meals' => ['breakfast', 'dinner'], 'servings' => 1, 'minutes' => 15,
        'ingredients' => ['egg' => 165, 'milk-2-5' => 50, 'tomato' => 100, 'bell-pepper' => 50, 'hard-cheese' => 20, 'olive-oil' => 5],
        'recipe' => 'Обжарьте нарезанные перец и помидор 2–3 минуты. Взбейте три яйца с молоком, залейте овощи, посыпьте тёртым сыром и готовьте под крышкой на слабом огне 5–7 минут.',
    ],
    [
        'slug' => 'cottage-cheese-berries', 'name' => 'Творог с ягодами и мёдом',
        'meals' => ['breakfast', 'snack'], 'servings' => 1, 'minutes' => 5,
        'ingredients' => ['cottage-cheese-5' => 200, 'strawberry' => 100, 'honey' => 10],
        'recipe' => 'Выложите творог в миску, добавьте ягоды (свежие или размороженные) и полейте мёдом.',
    ],
    [
        'slug' => 'baked-syrniki', 'name' => 'Сырники запечённые',
        'meals' => ['breakfast'], 'servings' => 2, 'minutes' => 35,
        'ingredients' => ['cottage-cheese-5' => 400, 'egg' => 55, 'wheat-flour' => 40, 'sugar' => 15],
        'recipe' => 'Разомните творог с яйцом, сахаром и мукой. Сформируйте сырники, выложите на пергамент и запекайте 25 минут при 180 °C, перевернув в середине.',
    ],
    [
        'slug' => 'avocado-egg-toast', 'name' => 'Тост с авокадо и яйцом',
        'meals' => ['breakfast', 'snack'], 'servings' => 1, 'minutes' => 10,
        'ingredients' => ['bread-wholegrain' => 60, 'avocado' => 70, 'egg' => 110],
        'recipe' => 'Подсушите два ломтика хлеба. Разомните авокадо вилкой, намажьте на хлеб. Сверху — два варёных или жареных без масла яйца, соль и перец.',
    ],
    [
        'slug' => 'oat-pancake-cottage-cheese', 'name' => 'Овсяноблин с творогом',
        'meals' => ['breakfast'], 'servings' => 1, 'minutes' => 15,
        'ingredients' => ['oats' => 40, 'egg' => 110, 'milk-2-5' => 50, 'cottage-cheese-5' => 100],
        'recipe' => 'Смешайте хлопья, два яйца и молоко, дайте постоять 5 минут. Выпекайте блин на антипригарной сковороде под крышкой по 2–3 минуты с каждой стороны. Начините творогом и сложите пополам.',
    ],
    [
        'slug' => 'overnight-oats', 'name' => 'Ленивая овсянка с йогуртом и яблоком',
        'meals' => ['breakfast', 'snack'], 'servings' => 1, 'minutes' => 5,
        'ingredients' => ['oats' => 50, 'greek-yogurt' => 150, 'apple' => 100, 'almonds' => 15, 'honey' => 10],
        'recipe' => 'Смешайте хлопья с йогуртом и оставьте в холодильнике на ночь. Утром добавьте нарезанное яблоко, миндаль и мёд.',
    ],

    // --- Обеды и ужины ---
    [
        'slug' => 'chicken-buckwheat-vegetables', 'name' => 'Куриная грудка с гречкой и овощами',
        'meals' => ['lunch', 'dinner'], 'servings' => 2, 'minutes' => 30,
        'ingredients' => ['chicken-breast' => 300, 'buckwheat-dry' => 140, 'broccoli' => 200, 'carrot' => 100, 'olive-oil' => 10],
        'recipe' => 'Отварите гречку (1 часть крупы на 2 части воды, 15 минут). Грудку нарежьте и обжарьте на масле 7–8 минут, добавьте морковь и брокколи, влейте немного воды и тушите под крышкой 7 минут.',
    ],
    [
        'slug' => 'chicken-rice-stir-fry', 'name' => 'Курица с рисом и овощами на сковороде',
        'meals' => ['lunch', 'dinner'], 'servings' => 2, 'minutes' => 30,
        'ingredients' => ['chicken-breast' => 300, 'rice-dry' => 140, 'bell-pepper' => 150, 'onion' => 75, 'frozen-vegetables' => 200, 'sunflower-oil' => 10, 'soy-sauce' => 20],
        'recipe' => 'Отварите рис. Обжарьте курицу кусочками с луком 6–7 минут, добавьте перец и овощную смесь, готовьте ещё 5 минут. Влейте соевый соус, смешайте с рисом и прогрейте.',
    ],
    [
        'slug' => 'turkey-tomato-pasta', 'name' => 'Паста с индейкой в томатном соусе',
        'meals' => ['lunch', 'dinner'], 'servings' => 2, 'minutes' => 30,
        'ingredients' => ['turkey-breast' => 300, 'pasta-dry' => 160, 'tomato' => 300, 'onion' => 75, 'tomato-paste' => 30, 'olive-oil' => 10, 'hard-cheese' => 30],
        'recipe' => 'Отварите макароны до состояния аль денте. Индейку нарежьте и обжарьте с луком, добавьте нарезанные помидоры и томатную пасту, тушите 10 минут. Смешайте с макаронами и посыпьте сыром.',
    ],
    [
        'slug' => 'beef-potato-stew', 'name' => 'Говядина, тушённая с картофелем',
        'meals' => ['lunch', 'dinner'], 'servings' => 3, 'minutes' => 90,
        'ingredients' => ['beef-lean' => 400, 'potato' => 600, 'carrot' => 100, 'onion' => 100, 'sunflower-oil' => 15],
        'recipe' => 'Обжарьте говядину кусочками до корочки, добавьте лук и морковь. Залейте водой и тушите под крышкой 50–60 минут. Добавьте картофель кубиками и готовьте ещё 20 минут.',
    ],
    [
        'slug' => 'baked-salmon-rice-broccoli', 'name' => 'Лосось запечённый с рисом и брокколи',
        'meals' => ['lunch', 'dinner'], 'servings' => 2, 'minutes' => 30,
        'ingredients' => ['salmon' => 300, 'rice-dry' => 140, 'broccoli' => 300, 'olive-oil' => 10],
        'recipe' => 'Посолите рыбу, сбрызните маслом и запекайте 15–18 минут при 200 °C. Отварите рис, брокколи приготовьте на пару или отварите 5 минут.',
    ],
    [
        'slug' => 'baked-cod-vegetables', 'name' => 'Треска с овощами в духовке',
        'meals' => ['lunch', 'dinner'], 'servings' => 2, 'minutes' => 35,
        'ingredients' => ['cod' => 400, 'zucchini' => 300, 'tomato' => 200, 'onion' => 75, 'olive-oil' => 15],
        'recipe' => 'Нарежьте кабачок, помидоры и лук, выложите в форму, сбрызните маслом. Сверху — филе трески, соль и специи. Запекайте 25 минут при 190 °C.',
    ],
    [
        'slug' => 'lentil-soup', 'name' => 'Суп из чечевицы',
        'meals' => ['lunch', 'dinner'], 'servings' => 4, 'minutes' => 40,
        'ingredients' => ['lentils-dry' => 200, 'carrot' => 100, 'onion' => 100, 'tomato' => 200, 'olive-oil' => 15],
        'recipe' => 'Обжарьте лук и морковь на масле, добавьте помидоры и тушите 5 минут. Всыпьте промытую чечевицу, залейте 1,2 л воды и варите 25 минут. Посолите в конце, по желанию пробейте блендером.',
    ],
    [
        'slug' => 'beef-bean-chili', 'name' => 'Чили с говяжьим фаршем и фасолью',
        'meals' => ['lunch', 'dinner'], 'servings' => 4, 'minutes' => 45,
        'ingredients' => ['beef-mince' => 400, 'beans-canned' => 400, 'tomato' => 400, 'onion' => 100, 'bell-pepper' => 150, 'tomato-paste' => 40],
        'recipe' => 'Обжарьте фарш с луком без масла, слейте лишний жир. Добавьте перец, помидоры, томатную пасту и специи, тушите 20 минут. Добавьте фасоль и готовьте ещё 10 минут.',
    ],
    [
        'slug' => 'tofu-vegetable-rice', 'name' => 'Тофу с овощами и рисом',
        'meals' => ['lunch', 'dinner'], 'servings' => 2, 'minutes' => 30,
        'ingredients' => ['tofu' => 300, 'rice-dry' => 140, 'bell-pepper' => 150, 'broccoli' => 200, 'carrot' => 100, 'soy-sauce' => 30, 'sunflower-oil' => 10],
        'recipe' => 'Отварите рис. Тофу нарежьте кубиками и обжарьте до корочки, отложите. Обжарьте овощи 5–6 минут, верните тофу, влейте соевый соус и прогрейте. Подавайте с рисом.',
    ],
    [
        'slug' => 'chickpea-bulgur-vegetables', 'name' => 'Нут с овощами и булгуром',
        'meals' => ['lunch', 'dinner'], 'servings' => 3, 'minutes' => 30,
        'ingredients' => ['chickpeas-cooked' => 400, 'bulgur-dry' => 120, 'tomato' => 300, 'onion' => 100, 'spinach' => 100, 'olive-oil' => 15],
        'recipe' => 'Отварите булгур (15 минут). Обжарьте лук, добавьте помидоры и специи, тушите 5 минут. Добавьте готовый нут и шпинат, прогрейте 5 минут. Подавайте с булгуром.',
    ],
    [
        'slug' => 'tuna-egg-salad', 'name' => 'Салат с тунцом, яйцом и овощами',
        'meals' => ['lunch', 'dinner'], 'servings' => 2, 'minutes' => 15,
        'ingredients' => ['tuna-canned' => 150, 'egg' => 110, 'cucumber' => 150, 'tomato' => 150, 'lettuce' => 80, 'olive-oil' => 10],
        'recipe' => 'Отварите яйца вкрутую и нарежьте. Порвите листья салата, нарежьте огурец и помидоры, добавьте тунец без жидкости. Заправьте маслом, посолите.',
    ],
    [
        'slug' => 'chicken-feta-salad', 'name' => 'Салат с курицей, фетой и овощами',
        'meals' => ['lunch', 'dinner'], 'servings' => 2, 'minutes' => 20,
        'ingredients' => ['chicken-breast' => 200, 'feta' => 60, 'cucumber' => 200, 'tomato' => 200, 'bell-pepper' => 100, 'lettuce' => 60, 'olive-oil' => 15],
        'recipe' => 'Отварите или обжарьте грудку без масла, нарежьте. Крупно нарежьте овощи, добавьте кубики феты и курицу, заправьте маслом.',
    ],
    [
        'slug' => 'chicken-oat-cutlets', 'name' => 'Котлеты из куриного фарша с овсянкой',
        'meals' => ['lunch', 'dinner'], 'servings' => 4, 'minutes' => 40,
        'ingredients' => ['chicken-mince' => 500, 'oats' => 50, 'egg' => 55, 'onion' => 75],
        'recipe' => 'Смешайте фарш с хлопьями, яйцом и тёртым луком, посолите, дайте постоять 10 минут. Сформируйте котлеты и запекайте 25 минут при 190 °C.',
    ],
    [
        'slug' => 'chicken-plov', 'name' => 'Плов с курицей',
        'meals' => ['lunch', 'dinner'], 'servings' => 4, 'minutes' => 50,
        'ingredients' => ['chicken-thigh' => 400, 'rice-dry' => 200, 'carrot' => 200, 'onion' => 150, 'sunflower-oil' => 20],
        'recipe' => 'Обжарьте курицу кусочками, добавьте лук и морковь соломкой, готовьте 7 минут. Всыпьте промытый рис, залейте водой на палец выше риса, посолите и готовьте под крышкой на слабом огне 20–25 минут.',
    ],
    [
        'slug' => 'navy-style-pasta', 'name' => 'Макароны по-флотски',
        'meals' => ['lunch', 'dinner'], 'servings' => 3, 'minutes' => 25,
        'ingredients' => ['beef-mince' => 300, 'pasta-dry' => 200, 'onion' => 100, 'sunflower-oil' => 10],
        'recipe' => 'Отварите макароны. Обжарьте лук, добавьте фарш и готовьте 10 минут, разбивая комочки. Смешайте с макаронами и прогрейте 2 минуты.',
    ],
    [
        'slug' => 'chicken-vegetable-ragout', 'name' => 'Овощное рагу с курицей',
        'meals' => ['lunch', 'dinner'], 'servings' => 3, 'minutes' => 45,
        'ingredients' => ['chicken-breast' => 400, 'potato' => 300, 'zucchini' => 300, 'cabbage' => 200, 'carrot' => 100, 'onion' => 75, 'sunflower-oil' => 10],
        'recipe' => 'Обжарьте курицу с луком и морковью. Добавьте картофель, через 10 минут — кабачок и капусту. Влейте стакан воды и тушите под крышкой 20 минут.',
    ],
    [
        'slug' => 'buckwheat-mushrooms', 'name' => 'Гречка с грибами и луком',
        'meals' => ['lunch', 'dinner'], 'servings' => 2, 'minutes' => 30,
        'ingredients' => ['buckwheat-dry' => 160, 'mushrooms' => 300, 'onion' => 100, 'sunflower-oil' => 15],
        'recipe' => 'Отварите гречку. Обжарьте лук до золотистого цвета, добавьте нарезанные шампиньоны и готовьте 8–10 минут. Смешайте с гречкой.',
    ],

    // --- Перекусы ---
    [
        'slug' => 'protein-banana-shake', 'name' => 'Протеиновый коктейль с бананом',
        'meals' => ['snack'], 'servings' => 1, 'minutes' => 5,
        'ingredients' => ['whey-protein' => 30, 'milk-2-5' => 250, 'banana' => 120],
        'recipe' => 'Взбейте в блендере молоко, банан и порцию протеина.',
    ],
    [
        'slug' => 'homemade-gainer', 'name' => 'Домашний коктейль для набора массы',
        'meals' => ['snack', 'breakfast'], 'servings' => 1, 'minutes' => 5,
        'ingredients' => ['milk-2-5' => 300, 'oats' => 50, 'banana' => 120, 'peanut-butter' => 20, 'whey-protein' => 30],
        'recipe' => 'Взбейте все ингредиенты в блендере до однородности. Удобно после тренировки или когда не получается добрать калории едой.',
    ],
    [
        'slug' => 'turkey-cheese-sandwich', 'name' => 'Бутерброд с индейкой и сыром',
        'meals' => ['snack', 'breakfast'], 'servings' => 1, 'minutes' => 5,
        'ingredients' => ['bread-wholegrain' => 60, 'turkey-ham' => 50, 'hard-cheese' => 20, 'cucumber' => 50, 'lettuce' => 15],
        'recipe' => 'На два ломтика хлеба выложите листья салата, ветчину, сыр и кружочки огурца.',
    ],
    [
        'slug' => 'rice-cakes-peanut-butter', 'name' => 'Рисовые хлебцы с арахисовой пастой и бананом',
        'meals' => ['snack'], 'servings' => 1, 'minutes' => 5,
        'ingredients' => ['rice-cakes' => 27, 'peanut-butter' => 30, 'banana' => 100],
        'recipe' => 'Намажьте три хлебца арахисовой пастой, сверху разложите кружочки банана.',
    ],
    [
        'slug' => 'hummus-vegetables', 'name' => 'Хумус с овощными палочками',
        'meals' => ['snack'], 'servings' => 1, 'minutes' => 5,
        'ingredients' => ['hummus' => 80, 'carrot' => 100, 'cucumber' => 100, 'bell-pepper' => 100],
        'recipe' => 'Нарежьте морковь, огурец и перец брусочками и подавайте с хумусом.',
    ],
];
