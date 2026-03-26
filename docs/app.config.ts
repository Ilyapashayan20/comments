export default defineAppConfig({
    docus: {
        title: 'Comments',
        description: 'A full-featured commenting system for Filament panels with threaded replies, @mentions, emoji reactions, and real-time updates.',
        header: {
            logo: {
                alt: 'Comments Logo',
            }
        }
    },
    seo: {
        title: 'Comments',
        description: 'A full-featured commenting system for Filament panels with threaded replies, @mentions, emoji reactions, and real-time updates.',
    },
    github: {
        repo: 'comments',
        owner: 'Relaticle',
        edit: true,
        rootDir: 'docs'
    },
    socials: {
        discord: 'https://discord.gg/b9WxzUce4Q'
    },
    ui: {
        colors: {
            primary: 'violet',
            neutral: 'zinc'
        }
    },
    uiPro: {
        pageHero: {
            slots: {
                container: 'flex flex-col lg:grid py-16 sm:py-20 lg:py-24 gap-16 sm:gap-y-2'
            }
        }
    },
    toc: {
        title: 'On this page',
        bottom: {
            title: 'Ecosystem',
            edit: 'https://github.com/Relaticle/comments',
            links: [
                {
                    icon: 'i-simple-icons-laravel',
                    label: 'FilaForms',
                    to: 'https://filaforms.app',
                    target: '_blank'
                },
                {
                    icon: 'i-lucide-sliders',
                    label: 'Custom Fields',
                    to: 'https://relaticle.github.io/custom-fields',
                    target: '_blank'
                },
                {
                    icon: 'i-lucide-kanban',
                    label: 'Flowforge',
                    to: 'https://relaticle.github.io/flowforge',
                    target: '_blank'
                }
            ]
        }
    }
})
