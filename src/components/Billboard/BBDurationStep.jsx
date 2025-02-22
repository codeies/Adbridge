import { Card, CardContent } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import useCampaignStore from "@/stores/useCampaignStore";

const BBDurationStep = () => {
    const { billboard, setCurrentStep, setBillboardFilters } = useCampaignStore();

    const durations = [
        { key: 'daily', label: 'Daily' },
        { key: 'daily_premium', label: 'Daily Premium' },
        { key: 'weekly', label: 'Weekly' },
        { key: 'weekly_premium', label: 'Weekly Premium' },
        { key: 'monthly', label: 'Monthly' },
        { key: 'monthly_premium', label: 'Monthly Premium' },
    ];

    return (
        <div className="space-y-6">
            <div className="flex items-center space-x-4">
                <Button variant="outline" onClick={() => setCurrentStep(2)}>← Back</Button>
                <h2 className="text-2xl font-semibold">Select Duration</h2>
            </div>

            <div className="grid grid-cols-3 gap-4">
                {durations.map(({ key, label }) => (
                    <Card
                        key={key}
                        className="cursor-pointer hover:bg-gray-50"
                        onClick={() => {
                            setBillboardFilters({ selectedDuration: key });
                            setCurrentStep(4); // Move to the next step
                        }}
                    >
                        <CardContent className="p-4">
                            <h3 className="font-semibold">{label}</h3>
                            <p className="text-sm text-gray-600">${billboard.selectedBillboard?.pricing[key]} / {label.toLowerCase()}</p>
                        </CardContent>
                    </Card>
                ))}
            </div>
        </div>
    );
};

export default BBDurationStep;
