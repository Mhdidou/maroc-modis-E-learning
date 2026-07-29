import { Plus, X } from 'lucide-react';

/**
 * Éditeur de la liste des mauvaises réponses (partagé quiz + checkpoint vidéo).
 * Au moins une réponse fausse est requise côté serveur.
 */
export default function ReponsesFausses({
    valeurs,
    onChange,
    erreur,
}: {
    valeurs: string[];
    onChange: (v: string[]) => void;
    erreur?: string;
}) {
    const champ =
        'block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:border-[#1C9AD6] focus:outline-none focus:ring-2 focus:ring-[#1C9AD6]/30';

    return (
        <div>
            <label className="mb-1.5 block text-sm font-semibold">
                Mauvaises réponses
            </label>
            <div className="space-y-2">
                {valeurs.map((v, i) => (
                    <div key={i} className="flex items-center gap-2">
                        <input
                            type="text"
                            value={v}
                            onChange={(e) => {
                                const copie = [...valeurs];
                                copie[i] = e.target.value;
                                onChange(copie);
                            }}
                            className={champ}
                            placeholder={`Mauvaise réponse ${i + 1}`}
                        />
                        {valeurs.length > 1 && (
                            <button
                                type="button"
                                onClick={() =>
                                    onChange(valeurs.filter((_, j) => j !== i))
                                }
                                className="shrink-0 rounded-lg p-1.5 text-slate-400 hover:bg-red-50 hover:text-[#E23744]"
                                aria-label="Retirer"
                            >
                                <X className="h-4 w-4" />
                            </button>
                        )}
                    </div>
                ))}
            </div>
            {valeurs.length < 5 && (
                <button
                    type="button"
                    onClick={() => onChange([...valeurs, ''])}
                    className="mt-2 inline-flex items-center gap-1 text-xs font-semibold text-[#1C9AD6] hover:underline"
                >
                    <Plus className="h-3.5 w-3.5" />
                    Ajouter une réponse
                </button>
            )}
            {erreur && <p className="mt-1 text-xs text-[#E23744]">{erreur}</p>}
        </div>
    );
}
