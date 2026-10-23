export type DefaultCategorySummary = {
    name: string;
    description: string;
};

export function DefaultCategoriesSummary({
    categories,
}: {
    categories: DefaultCategorySummary[];
}) {
    return (
        <ul className="space-y-3">
            {categories.map((category) => (
                <li key={category.name} className="rounded-lg border p-3">
                    <p className="font-medium">{category.name}</p>
                    <p className="mt-1 line-clamp-2 text-sm text-muted-foreground">
                        {category.description}
                    </p>
                </li>
            ))}
        </ul>
    );
}
