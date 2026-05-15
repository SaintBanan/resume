package com.example.bt.adapters;

import android.content.Context;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.TextView;

import androidx.annotation.NonNull;
import androidx.recyclerview.widget.RecyclerView;

import com.example.bt.R;
import com.example.bt.interfaces.MainListener;
import com.example.bt.model.Deal;
import com.example.bt.modules.Date;
import com.google.android.material.card.MaterialCardView;

import java.util.ArrayList;
import java.util.List;

public class DealsAdapter 
    extends RecyclerView.Adapter<DealsAdapter.ViewHolder> 
    implements com.example.bt.interfaces.DealsAdapter {

    private Context context;
    private List<Deal> deals;
    private boolean is_open;
    private boolean load_more;

    private MainListener mainListener;
    private NewDealsAdapter.GetDealsListener getDealsListener;
    private NewDealsAdapter.ShowListListener showListListener;

    public DealsAdapter(Context context, MainListener mainListener, boolean is_open) {
        this.context = context;
        this.mainListener = mainListener;
        this.is_open = is_open;
        resetList();
    }

    @NonNull
    @Override
    public DealsAdapter.ViewHolder onCreateViewHolder(@NonNull ViewGroup parent, int viewType) {
        View view = LayoutInflater.from(context).inflate(R.layout.deal_item, parent, false);
        return new DealsAdapter.ViewHolder(view);
    }

    @Override
    public void onBindViewHolder(@NonNull ViewHolder holder, int position) {

        Deal deal = deals.get(position);
        String date = is_open ? deal.getAcceptDatetime() : deal.getClosedDatetime();

        holder.address.setText(is_open ? deal.getAddress().getFull() : deal.getAddress().getPart());
        holder.datetime.setText(date);
        holder.dealName.setText(deal.getName());
        holder.status.setText(deal.getStage().getName());
        holder.clientName.setText(deal.getClient().getName());
        holder.card.setOnClickListener(v -> mainListener.setDealFragment(deal.getId()));

        if (is_open && !deal.getDescription().equals("")) {

            holder.description.setText(deal.getDescription());
            holder.description.setVisibility(View.VISIBLE);
        }
        else {

            holder.description.setVisibility(View.GONE);
        }

        // Подгрузить данные
        if (holder.getBindingAdapterPosition() == getItemCount() - 3 && load_more) {

            getDealsListener.execute();
        }
    }

    @Override
    public int getItemCount() {
        
        return deals.size();
    }

    // Обнулить список
    public void resetList() {

        deals = new ArrayList<>();
        load_more = true;
    }

    // Добавить новые элементы к имеющемуся списку
    public void expandList(List<Deal> data) {

        if (data.size() > 0) {

            int offset = deals.size();

            if (offset == 0) {

                // Новый список
                deals = data;
                notifyDataSetChanged();
            }
            else {

                // Дополнение списка
                deals.addAll(data);
                notifyItemRangeInserted(offset, deals.size());
            }
        }
        else {
            load_more = false;
        }
    }

    public void add(Deal deal) {

        int pos = getPosition(deal.getId());

        if (pos == -1) {

            deals.add(0, deal);
            notifyItemInserted(0);

            if (deals.size() == 1) {
                showListListener.execute(true);
            }
        }
    }

    public void updateDeal(Deal data) {

        int pos = getPosition(data.getId());

        if (pos != -1) {

            Deal deal = deals.get(pos);

            if (!deal.equal(data)) {
                deal.copy(data);
                notifyItemChanged(pos);
            }
        }
    }

    public void removeDeal(String deal_id) {

        int pos = getPosition(deal_id);

        if (pos != -1) {

            deals.remove(pos);
            notifyItemRemoved(pos);

            if (deals.size() == 0) {
                showListListener.execute(false);
            }
        }
    }

    public int getPosition(String deal_id) {

        for (int i = 0; i < deals.size(); i++) {

            if (deals.get(i).getId().equals(deal_id)) {
                return i;
            }
        }

        return -1;
    }

    public class ViewHolder extends RecyclerView.ViewHolder {

        TextView datetime;
        TextView dealName;
        TextView clientName;
        TextView status;
        TextView address;
        TextView description;
        MaterialCardView card;

        ViewHolder(View view) {
            super(view);

            datetime = view.findViewById(R.id.datetime);
            dealName = view.findViewById(R.id.title);
            address = view.findViewById(R.id.address);
            description = view.findViewById(R.id.description);
            status = view.findViewById(R.id.status);
            card = view.findViewById(R.id.cardView);
            clientName = view.findViewById(R.id.clientName);
        }
    }

    public void setOnItemGetDealsListener(GetDealsListener listener) {
        getDealsListener = listener;
    }

    public void setOnItemShowListListener(ShowListListener listener) {
        showListListener = listener;
    }
}
