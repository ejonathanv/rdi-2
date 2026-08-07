import { Head, Link } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import rdiLogo from '../../img/rdi-logo.svg';
import { login } from '@/routes';

const features = [
    {
        title: 'Rondines con evidencia',
        description:
            'Recorridos guiados por QR, cuestionarios por punto y registro fotográfico. Cada visita queda auditada con hora, guardia y resultado.',
        image: 'https://placehold.co/960x720/13395f/e8eef4?text=Rondines',
        alt: 'Captura del panel de rondines: listado de recorridos realizados con estado, duración y detalle por punto',
    },
    {
        title: 'Incidencias con inteligencia',
        description:
            'El guardia describe lo que ocurre en lenguaje libre. RDI limpia el reporte, lo categoriza y notifica a los contactos correctos.',
        image: 'https://placehold.co/960x720/0f2a45/dce6f0?text=Incidencias',
        alt: 'Pantalla de reporte de incidencia en móvil con mensaje, fotos y marca de urgente',
    },
    {
        title: 'Alertas en tiempo real',
        description:
            'WhatsApp y SMS a contactos del área cuando hay un punto urgente, una incidencia o una activación del botón de pánico.',
        image: 'https://placehold.co/960x720/1a4a75/f0f5fa?text=Alertas',
        alt: 'Teléfono mostrando notificación WhatsApp de alerta RDI con área, guardia y detalle del evento',
    },
] as const;

const benefits = [
    {
        title: 'Trazabilidad completa',
        description:
            'Quién revisó qué punto, cuándo y con qué evidencia. Ideal para auditorías y supervisión.',
    },
    {
        title: 'Respuesta más rápida',
        description:
            'Los contactos enterados al instante reducen el tiempo entre el hallazgo y la acción.',
    },
    {
        title: 'Menos fricción en campo',
        description:
            'Flujo pensado para el guardia: escanear, confirmar y reportar sin formularios eternos.',
    },
    {
        title: 'Visibilidad para la planta',
        description:
            'Panel con indicadores de urgentes, incidencias, pánicos y rondines en curso.',
    },
] as const;

const steps = [
    {
        n: '01',
        title: 'Configura el área',
        description:
            'Define recorridos, puntos con QR, contactos y categorías de incidencia.',
    },
    {
        n: '02',
        title: 'Opera el turno',
        description:
            'El guardia inicia el recorrido, escanea puntos y reporta novedades o emergencias.',
    },
    {
        n: '03',
        title: 'Supervisa y actúa',
        description:
            'Admin y contactos reciben alertas y consultan el historial con evidencia.',
    },
] as const;

function useReveal() {
    const [ready, setReady] = useState(false);

    useEffect(() => {
        const id = requestAnimationFrame(() => setReady(true));

        return () => cancelAnimationFrame(id);
    }, []);

    return ready;
}

export default function LandingPage() {
    const ready = useReveal();

    return (
        <>
            <Head title="RDI — Recorridos e incidencias con control en tiempo real" />

            <div className="min-h-screen bg-[#f7f9fb] text-[#0f1c2a] antialiased">
                <header className="absolute inset-x-0 top-0 z-20">
                    <div className="mx-auto flex h-16 max-w-6xl items-center justify-between px-4 sm:h-20 sm:px-6">
                        <a href="#inicio" className="flex items-center gap-3">
                            <img
                                src={rdiLogo}
                                alt="Logotipo RDI"
                                className="h-[2.5875rem] w-auto sm:h-[2.875rem]"
                            />
                        </a>
                        <nav className="hidden items-center gap-8 text-sm text-white/85 md:flex">
                            <a
                                href="#capacidades"
                                className="transition-colors hover:text-white"
                            >
                                Capacidades
                            </a>
                            <a
                                href="#como-funciona"
                                className="transition-colors hover:text-white"
                            >
                                Cómo funciona
                            </a>
                            <a
                                href="#beneficios"
                                className="transition-colors hover:text-white"
                            >
                                Beneficios
                            </a>
                        </nav>
                        <Link
                            href={login()}
                            className="rounded-md bg-white px-4 py-2 text-sm font-medium text-[#13395f] transition hover:bg-white/90"
                        >
                            Iniciar sesión
                        </Link>
                    </div>
                </header>

                <section
                    id="inicio"
                    className="relative flex min-h-[100svh] flex-col justify-end overflow-hidden"
                >
                    <img
                        src="https://placehold.co/1920x1080/13395f/8fa8c4?text=Operacion+de+seguridad"
                        alt="Fotografía full-bleed de guardia en planta industrial o perímetro realizando un recorrido nocturno con linterna"
                        className="absolute inset-0 size-full object-cover"
                    />
                    <div className="absolute inset-0 bg-gradient-to-t from-[#0a1a2e] via-[#13395f]/85 to-[#13395f]/55" />
                    <div
                        className={`relative z-10 mx-auto w-full max-w-6xl px-4 pb-16 pt-28 sm:px-6 sm:pb-24 transition-all duration-700 ${
                            ready
                                ? 'translate-y-0 opacity-100'
                                : 'translate-y-4 opacity-0'
                        }`}
                    >
                        <p className="mb-4 text-sm font-semibold tracking-[0.28em] text-white/70 uppercase">
                            RDI
                        </p>
                        <h1 className="max-w-3xl text-4xl font-semibold tracking-tight text-white sm:text-5xl lg:text-6xl">
                            Control operativo de recorridos e incidencias,
                            cuando importa.
                        </h1>
                        <p className="mt-5 max-w-xl text-base leading-relaxed text-white/80 sm:text-lg">
                            La plataforma SaaS que conecta al personal en campo
                            con supervisión en tiempo real: evidencia, alertas y
                            trazabilidad en un solo sistema.
                        </p>
                        <div className="mt-8 flex flex-wrap gap-3">
                            <a
                                href="#capacidades"
                                className="rounded-md bg-white px-5 py-3 text-sm font-medium text-[#13395f] transition hover:bg-white/90"
                            >
                                Ver capacidades
                            </a>
                            <Link
                                href={login()}
                                className="rounded-md border border-white/35 px-5 py-3 text-sm font-medium text-white transition hover:bg-white/10"
                            >
                                Acceder a la plataforma
                            </Link>
                        </div>
                    </div>
                </section>

                <section className="border-b border-[#d8e0ea] bg-white py-16 sm:py-20">
                    <div className="mx-auto grid max-w-6xl gap-10 px-4 sm:px-6 lg:grid-cols-[1fr_1.1fr] lg:items-center">
                        <div
                            className={`transition-all delay-100 duration-700 ${
                                ready
                                    ? 'translate-y-0 opacity-100'
                                    : 'translate-y-3 opacity-0'
                            }`}
                        >
                            <h2 className="text-3xl font-semibold tracking-tight text-[#13395f] sm:text-4xl">
                                De la bitácora en papel a la operación con
                                evidencia.
                            </h2>
                            <p className="mt-4 text-base leading-relaxed text-[#3d4f63]">
                                RDI nace para plantas y operaciones de seguridad
                                que necesitan saber, sin ambigüedad, si el
                                recorrido se cumplió, qué se encontró y quién
                                debe enterarse ahora.
                            </p>
                        </div>
                        <img
                            src="https://placehold.co/1000x700/e8eef4/13395f?text=Antes+y+despues"
                            alt="Composición lado a lado: bitácora en papel vs tablet con dashboard RDI de cumplimiento de recorridos"
                            className="w-full object-cover"
                        />
                    </div>
                </section>

                <section id="capacidades" className="py-20 sm:py-24">
                    <div className="mx-auto max-w-6xl px-4 sm:px-6">
                        <div className="max-w-2xl">
                            <p className="text-sm font-semibold tracking-[0.2em] text-[#13395f]/70 uppercase">
                                Capacidades
                            </p>
                            <h2 className="mt-3 text-3xl font-semibold tracking-tight text-[#13395f] sm:text-4xl">
                                Todo el ciclo operativo en una plataforma.
                            </h2>
                            <p className="mt-4 text-base leading-relaxed text-[#3d4f63]">
                                Diseñado para el guardia en campo y para quien
                                supervisa desde el panel: menos fricción, más
                                control.
                            </p>
                        </div>

                        <div className="mt-14 space-y-20">
                            {features.map((feature, index) => (
                                <article
                                    key={feature.title}
                                    className={`grid items-center gap-10 lg:grid-cols-2 ${
                                        index % 2 === 1
                                            ? 'lg:[&>div:first-child]:order-2'
                                            : ''
                                    }`}
                                >
                                    <div>
                                        <h3 className="text-2xl font-semibold text-[#13395f]">
                                            {feature.title}
                                        </h3>
                                        <p className="mt-3 text-base leading-relaxed text-[#3d4f63]">
                                            {feature.description}
                                        </p>
                                    </div>
                                    <img
                                        src={feature.image}
                                        alt={feature.alt}
                                        className="w-full object-cover shadow-[0_24px_60px_-28px_rgba(19,57,95,0.45)]"
                                    />
                                </article>
                            ))}
                        </div>
                    </div>
                </section>

                <section
                    id="como-funciona"
                    className="border-y border-[#d8e0ea] bg-white py-20 sm:py-24"
                >
                    <div className="mx-auto max-w-6xl px-4 sm:px-6">
                        <div className="max-w-2xl">
                            <p className="text-sm font-semibold tracking-[0.2em] text-[#13395f]/70 uppercase">
                                Cómo funciona
                            </p>
                            <h2 className="mt-3 text-3xl font-semibold tracking-tight text-[#13395f] sm:text-4xl">
                                Tres pasos. Operación continua.
                            </h2>
                        </div>
                        <ol className="mt-14 grid gap-10 md:grid-cols-3">
                            {steps.map((step) => (
                                <li key={step.n}>
                                    <span className="text-sm font-semibold tracking-widest text-[#13395f]/45">
                                        {step.n}
                                    </span>
                                    <h3 className="mt-3 text-xl font-semibold text-[#13395f]">
                                        {step.title}
                                    </h3>
                                    <p className="mt-2 text-sm leading-relaxed text-[#3d4f63]">
                                        {step.description}
                                    </p>
                                </li>
                            ))}
                        </ol>
                        <img
                            src="https://placehold.co/1200x480/13395f/c5d4e4?text=Flujo+operativo"
                            alt="Diagrama o collage del flujo: configurar recorridos, escanear QR en campo, panel de supervisión con alertas"
                            className="mt-14 w-full object-cover"
                        />
                    </div>
                </section>

                <section id="beneficios" className="py-20 sm:py-24">
                    <div className="mx-auto max-w-6xl px-4 sm:px-6">
                        <div className="max-w-2xl">
                            <p className="text-sm font-semibold tracking-[0.2em] text-[#13395f]/70 uppercase">
                                Beneficios
                            </p>
                            <h2 className="mt-3 text-3xl font-semibold tracking-tight text-[#13395f] sm:text-4xl">
                                Valor que se nota en cada turno.
                            </h2>
                        </div>
                        <div className="mt-12 grid gap-x-10 gap-y-12 sm:grid-cols-2">
                            {benefits.map((benefit) => (
                                <div key={benefit.title}>
                                    <h3 className="text-lg font-semibold text-[#13395f]">
                                        {benefit.title}
                                    </h3>
                                    <p className="mt-2 text-sm leading-relaxed text-[#3d4f63]">
                                        {benefit.description}
                                    </p>
                                </div>
                            ))}
                        </div>
                    </div>
                </section>

                <section className="relative overflow-hidden bg-[#13395f] py-20 sm:py-24">
                    <img
                        src="https://placehold.co/1600x600/0f2a45/5a7a9a?text=Confianza+operativa"
                        alt="Ambiente industrial sobrio al atardecer, sugiriendo control y presencia de seguridad en planta"
                        className="absolute inset-0 size-full object-cover opacity-35"
                    />
                    <div className="absolute inset-0 bg-[#13395f]/75" />
                    <div className="relative z-10 mx-auto max-w-3xl px-4 text-center sm:px-6">
                        <h2 className="text-3xl font-semibold tracking-tight text-white sm:text-4xl">
                            Lleva el control de tus recorridos a un estándar
                            profesional.
                        </h2>
                        <p className="mt-4 text-base text-white/80">
                            RDI concentra evidencia, alertas e indicadores para
                            que tu operación de seguridad sea medible y
                            accionable.
                        </p>
                        <Link
                            href={login()}
                            className="mt-8 inline-flex rounded-md bg-white px-6 py-3 text-sm font-medium text-[#13395f] transition hover:bg-white/90"
                        >
                            Iniciar sesión
                        </Link>
                    </div>
                </section>

                <footer className="bg-primary py-10">
                    <div className="mx-auto flex max-w-6xl flex-col gap-4 px-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                        <div className="flex items-center gap-3">
                            <img
                                src={rdiLogo}
                                alt="Logotipo RDI"
                                className="h-8 w-auto"
                            />
                            <span className="text-sm text-primary-foreground/75">
                                Recorridos · Incidencias · Alertas
                            </span>
                        </div>
                        <p className="text-sm text-primary-foreground/75">
                            © {new Date().getFullYear()} RDI. Plataforma de
                            control operativo.
                        </p>
                    </div>
                </footer>
            </div>
        </>
    );
}
