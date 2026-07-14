/**
 * Tuile de statistique brandée, réutilisée par les tableaux de bord.
 */
export default function StatCard({
    icon: Icon,
    label,
    value,
    accent,
}: {
    icon: React.ElementType;
    label: string;
    value: number | string;
    accent: string;
}) {
    return (
        <div className="rounded-2xl border border-slate-100 bg-white p-5 shadow-sm">
            <div
                className="mb-3 flex h-11 w-11 items-center justify-center rounded-xl"
                style={{ backgroundColor: `${accent}1A`, color: accent }}
            >
                <Icon className="h-5 w-5" strokeWidth={2.2} />
            </div>
            <div className="text-3xl font-extrabold text-[#1B2430]">{value}</div>
            <div className="mt-1 text-sm font-medium text-slate-500">{label}</div>
        </div>
    );
}
