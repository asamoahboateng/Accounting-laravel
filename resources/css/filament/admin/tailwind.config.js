import preset from '../../../../vendor/filament/filament/tailwind.config.preset'

export default {
    presets: [preset],
    content: [
        './app/Filament/Clusters/Expenses/**/*.php',
        './resources/views/filament/clusters/expenses/**/*.blade.php',
        './vendor/filament/**/*.blade.php',
    ],
}
