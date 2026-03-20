   const v_ = yf({
                themes: [y_, __],
                langs: [b_, ql, Pl],
                engine: i_()
            });
            window.highlight = function(e, t, n=!1, a=!1, r=1, i=null) {
                return v_.codeToHtml(e, {
                    lang: t,
                    themes: {
                        light: "light-plus",
                        dark: "dark-plus"
                    },
                    transformers: [{
                        pre(s) {
                            this.addClassToHast(s, ["bg-transparent!", n ? "truncate" : "w-fit min-w-full"])
                        },
                        line(s, o) {
                            if (!a)
                                return;
                            const c = r + o - 1
                              , l = i === o - 1
                              , u = {
                                type: "element",
                                tagName: "span",
                                properties: {
                                    className: ["mr-6 text-neutral-500! dark:text-neutral-600!", l ? "dark:text-white!" : ""]
                                },
                                children: [{
                                    type: "text",
                                    value: c.toString()
                                }]
                            };
                            s.children.unshift(u),
                            this.addClassToHast(s, ["inline-block w-full px-4 py-1 h-7 even:bg-white odd:bg-white/2 even:dark:bg-white/2 odd:dark:bg-white/4", l ? "bg-rose-200! dark:bg-rose-900!" : ""])
                        }
                    }]
                });
