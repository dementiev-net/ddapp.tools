import {src, dest, parallel, series, watch} from 'gulp'
import concat from 'gulp-concat'
import uglify from 'gulp-uglify-es'
import gulpSass from 'gulp-sass'
import * as sass from 'sass'
import autoprefixer from 'gulp-autoprefixer'
import cleancss from 'gulp-clean-css'
import svgSprite from 'gulp-svg-sprite'
import sourcemaps from 'gulp-sourcemaps'

// Инициализация Sass
const sassProcessor = gulpSass(sass)

// Пути к файлам
const paths = {
    styles: {
        src: './assets/css/src/main.scss',
        watch: './assets/css/src/**/*.scss',
        dest: './assets/css/'
    },
    scripts: {
        src: [
            './assets/js/src/script.js',
            './assets/js/src/hmarketing.js',
        ],
        dest: './assets/js/'
    },
    svg: {
        src: './assets/svg/sprite/*.svg',
        dest: './assets/images/'
    }
}

// Обработка JavaScript
const scripts = () => {
    return src(paths.scripts.src)
        .pipe(sourcemaps.init())
        .pipe(concat('script.min.js'))
        .pipe(uglify.default())
        .pipe(sourcemaps.write('./'))
        .pipe(dest(paths.scripts.dest))
}

// Обработка SCSS
const styles = () => {
    return src(paths.styles.src)
        .pipe(sourcemaps.init())
        .pipe(sassProcessor({
            outputStyle: 'expanded'
        }).on('error', sassProcessor.logError))
        .pipe(concat('styles.css'))
        .pipe(autoprefixer({
            cascade: false,
            grid: true,
            flexbox: 'no-2009'
        }))
        .pipe(cleancss({
            level: {
                1: {
                    specialComments: 0
                }
            },
            compatibility: 'ie8'
        }))
        .pipe(sourcemaps.write('./'))
        .pipe(dest(paths.styles.dest))
}

// Создание SVG спрайта
const svg = () => {
    return src(paths.svg.src)
        .pipe(svgSprite({
            mode: {
                stack: {
                    sprite: "../sprite.svg"
                }
            }
        }))
        .pipe(dest(paths.svg.dest))
}

// Отслеживание изменений
const startWatch = () => {
    watch(['./**/*.js', '!./**/*.min.js'], scripts)
    watch(paths.styles.watch, styles)
}

// Экспорт задач
export {scripts, styles, svg}

// Сборка для разработки
export const _dev = parallel(
    styles,
    scripts,
    svg,
    startWatch
)

// Продакшн сборка
export const _build = series(
    parallel(styles, scripts, svg)
)