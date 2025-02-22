// components/BillboardStep.jsx
import { Card, CardContent } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import useCampaignStore from "@/stores/useCampaignStore";

const BillboardStep = () => {
    const {
        billboard,
        setBillboardFilters,
        setCurrentStep,
        setCampaignType
    } = useCampaignStore();

    const categories = ['all', 'digital', 'static'];
    const locations = ['all', 'downtown', 'highway', 'mall'];

    const billboards = [
        {
            id: 1, name: 'Downtown LED', category: 'digital', location: 'downtown', size: '20x40ft',
            pricing: {
                daily: 1000, daily_premium: 1200,
                weekly: 6000, weekly_premium: 7000,
                monthly: 20000, monthly_premium: 25000
            }
        },
        {
            id: 2, name: 'Highway Static', category: 'static', location: 'highway', size: '30x60ft',
            pricing: {
                daily: 1500, daily_premium: 1800,
                weekly: 9000, weekly_premium: 10500,
                monthly: 30000, monthly_premium: 35000
            }
        }
    ];


    const filteredBillboards = billboards.filter((b) => {
        const matchesCategory = billboard.selectedCategory === "all" || b.category === billboard.selectedCategory;
        const matchesLocation = billboard.selectedLocation === "all" || b.location === billboard.selectedLocation;
        const matchesSearch = billboard.searchTerm ? b.name.toLowerCase().includes(billboard.searchTerm.toLowerCase()) : true;

        return matchesCategory && matchesLocation && matchesSearch;
    });


    return (
        <div className="space-y-6">
            <div className="flex items-center space-x-4">
                <Button
                    variant="outline"
                    onClick={() => {
                        setCurrentStep(1);
                        setCampaignType(null);
                    }}
                    className="flex items-center"
                >
                    ← Back
                </Button>
                <h2 className="text-2xl font-semibold">Select Billboard</h2>
            </div>

            <div className="flex gap-4 mb-6">
                <div className="flex-1">
                    <Input
                        type="text"
                        placeholder="Search billboards..."
                        value={billboard.searchTerm}
                        onChange={(e) => setBillboardFilters({ searchTerm: e.target.value })}
                        className="w-full"
                    />
                </div>
                <Select
                    value={billboard.selectedCategory}
                    onValueChange={(value) => setBillboardFilters({ selectedCategory: value })}
                >
                    <SelectTrigger className="w-48">
                        <SelectValue placeholder="Category" />
                    </SelectTrigger>
                    <SelectContent>
                        {categories.map(category => (
                            <SelectItem key={category} value={category}>
                                {category.charAt(0).toUpperCase() + category.slice(1)}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
                <Select
                    value={billboard.selectedLocation}
                    onValueChange={(value) => setBillboardFilters({ selectedLocation: value })}
                >
                    <SelectTrigger className="w-48">
                        <SelectValue placeholder="Location" />
                    </SelectTrigger>
                    <SelectContent>
                        {locations.map(location => (
                            <SelectItem key={location} value={location}>
                                {location.charAt(0).toUpperCase() + location.slice(1)}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            </div>

            <div className="grid grid-cols-3 gap-4">
                {filteredBillboards.map((billboard) => (
                    <Card
                        key={billboard.id} // ✅ Corrected placement
                        className="cursor-pointer hover:bg-gray-50"
                        onClick={() => {
                            console.log("Billboard selected:", billboard);
                            setBillboardFilters({ selectedBillboard: billboard });
                            setCurrentStep(3);
                        }}
                    >
                        <CardContent className="p-4">
                            <div className="aspect-video bg-gray-200 mb-4 rounded"></div>
                            <h3 className="font-semibold">{billboard.name}</h3>
                            <p className="text-sm text-gray-600">Size: {billboard.size}</p>
                            <p className="text-sm text-gray-600">Location: {billboard.location}</p>
                            <p className="text-sm font-semibold mt-2">${billboard.pricing.daily}/day</p>
                        </CardContent>
                    </Card>
                ))}
            </div>

        </div>
    );
};

export default BillboardStep;